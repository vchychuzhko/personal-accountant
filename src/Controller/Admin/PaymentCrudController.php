<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use App\Repository\ConfigurationRepository;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PaymentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly TagAwareCacheInterface $cache,
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $sortFields = $searchDto->getSort();

        if (isset($sortFields['amount_in_usd'])) {
            $sortDirection = $sortFields['amount_in_usd'];

            $qb->leftJoin('entity.balance', 'balance')
                ->leftJoin('balance.currency', 'currency')
                ->addSelect('(entity.amount / currency.rate) AS HIDDEN amount_in_usd')
                ->orderBy('amount_in_usd', $sortDirection);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        $timezone = $this->configurationRepository->getByName('timezone');

        return [
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            AssociationField::new('tag'),
            AssociationField::new('balance')
                ->setFormTypeOptions([
                    'query_builder' => function (EntityRepository $repository) {
                        $dateFrom = new \DateTime('first day of this month 00:00:00');
                        $dateTo = new \DateTime('first day of next month 00:00:00');

                        return $repository->createQueryBuilder('b')
                            ->leftJoin('b.payments', 'p', 'WITH', 'p.created_at BETWEEN :from AND :to')
                            ->setParameter('from', $dateFrom)
                            ->setParameter('to', $dateTo)
                            ->groupBy('b.id')
                            ->orderBy('COUNT(p.id)', 'DESC')
                        ;
                    },
                ]),
            NumberField::new('amount')
                ->formatValue(function ($value, Payment $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->setSortable(true)
                ->hideOnForm(),
            DateTimeField::new('created_at')
                ->setFormTypeOption('model_timezone', 'UTC')
                ->setFormTypeOption('view_timezone', $timezone)
                ->setFormat('dd.MM.yyyy HH:mm')
                ->setTimezone($timezone)
                ->setHelp($timezone),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('balance')
            ->add('tag')
            ->add('created_at')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['created_at' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicate = Action::new('duplicate')
            ->setIcon('fa fa-copy')
            ->linkToUrl(
                fn(Payment $entity) => $this->adminUrlGenerator
                    ->setAction(Action::EDIT)
                    ->setEntityId($entity->getId())
                    ->set('duplicate', '1')
                    ->generateUrl()
            );

        return parent::configureActions($actions)
            ->add(Crud::PAGE_DETAIL, $duplicate)
        ;
    }

    /**
     * @see https://github.com/EasyCorp/EasyAdminBundle/issues/3937#issuecomment-1255896369
     * @see self::udpateEntity()
     */
    public function edit(AdminContext $context)
    {
        if ($context->getRequest()->query->has('duplicate')) {
            /** @var Payment $entity */
            $entity = $context->getEntity()->getInstance();
            $cloned = clone $entity;
            $cloned->setCreatedAt(new \DateTimeImmutable('now'));
            $context->getEntity()->setInstance($cloned);
        }

        return parent::edit($context);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Payment $entityInstance
     * @return void
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $uow = $entityManager->getUnitOfWork();

        // Check if the edited entity is a duplicate
        if (!$uow->isInIdentityMap($entityInstance)) {
            $this->updateBalance($entityManager, $entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Payment $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->updateBalance($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    private function updateBalance(EntityManagerInterface $entityManager, Payment $entityInstance): void
    {
        $balance = $entityInstance->getBalance();

        $balance->setAmount($balance->getAmount() - $entityInstance->getAmount());

        $entityManager->persist($balance);
        $entityManager->flush();

        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);
    }
}
