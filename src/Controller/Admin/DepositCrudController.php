<?php

namespace App\Controller\Admin;

use App\Entity\Deposit;
use App\Utils\PriceUtils;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DepositCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Deposit::class;
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
        return [
            FormField::addColumn(8)
                ->addCssClass('w-40'),
            FormField::addFieldset(),
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            AssociationField::new('balance'),

            NumberField::new('amount')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('expected_profit')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                })
                ->onlyOnDetail(),

            FormField::addFieldset()
                ->onlyOnDetail(),
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->setSortable(true)
                ->hideOnForm(),
            NumberField::new('expected_profit_in_usd', 'Expected Profit in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->hideOnForm(),

            FormField::addColumn(4),
            FormField::addFieldset(),
            NumberField::new('interest')
                ->formatValue(function ($value) {
                    return $value . '%';
                })
                ->setHelp('Annual, in %'),
            NumberField::new('period')
                ->formatValue(function ($value) {
                    return $value . ' ' . ($value === 1 ? 'month' : ' months');
                })
                ->setHelp('Number of months'),
            DateField::new('start_date')
                ->setFormat('dd-MM-yyyy')
                ->hideOnIndex(),
            DateField::new('end_date')
                ->setFormat('dd-MM-yyyy'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('balance');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC']);
    }
}
