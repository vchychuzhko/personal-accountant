<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Exchange;
use App\Repository\ConfigurationRepository;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ExchangeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Exchange::class;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $qb->join('entity.balance_from', 'bf')
            ->andWhere('bf.admin = :admin')
            ->setParameter('admin', $this->getUser());

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var Admin $admin */
        $admin = $this->getUser();
        $timezone = $this->configurationRepository->getByName('timezone', $admin);

        return [
            AssociationField::new('balance_from')
                ->setFormTypeOptions([
                    'query_builder' => function (EntityRepository $repository) {
                        return $repository->createQueryBuilder('b')
                            ->andWhere('b.admin = :admin')
                            ->setParameter('admin', $this->getUser());
                    },
                ]),
            AssociationField::new('balance_to')
                ->setFormTypeOptions([
                    'query_builder' => function (EntityRepository $repository) {
                        return $repository->createQueryBuilder('b')
                            ->andWhere('b.admin = :admin')
                            ->setParameter('admin', $this->getUser());
                    },
                ]),
            NumberField::new('amount')
                ->formatValue(function ($value, Exchange $entity) {
                    $currency = $entity->getBalanceFrom()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('result')
                ->formatValue(function ($value, Exchange $entity) {
                    $currency = $entity->getBalanceTo()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('rate')
                ->formatValue(fn($value) => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.'))
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
            ->add('balance_from')
            ->add('balance_to')
            ->add('created_at')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['created_at' => 'DESC'])
        ;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Exchange $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $balanceFrom = $entityInstance->getBalanceFrom();
        $balanceTo = $entityInstance->getBalanceTo();

        $balanceFrom->setAmount($balanceFrom->getAmount() - $entityInstance->getAmount());
        $balanceTo->setAmount($balanceTo->getAmount() + $entityInstance->getResult());

        $entityManager->persist($balanceFrom);
        $entityManager->persist($balanceTo);
        $entityManager->flush();

        /** @var Admin $admin */
        $admin = $this->getUser();
        $this->cache->invalidateTags([DashboardController::getCacheTag($admin)]);

        parent::persistEntity($entityManager, $entityInstance);
    }
}
