<?php

namespace App\Controller\Admin;

use App\Entity\Income;
use App\Repository\ConfigurationRepository;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class IncomeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Income::class;
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
            AssociationField::new('balance'),
            NumberField::new('amount')
                ->formatValue(function ($value, Income $entity) {
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
                ->setFormat('dd-MM-yyyy HH:mm')
                ->setTimezone($timezone)
                ->setHelp($timezone),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('balance')
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
     * @param Income $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $balance = $entityInstance->getBalance();

        $balance->setAmount($balance->getAmount() + $entityInstance->getAmount());

        $entityManager->persist($balance);
        $entityManager->flush();

        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        parent::persistEntity($entityManager, $entityInstance);
    }
}
