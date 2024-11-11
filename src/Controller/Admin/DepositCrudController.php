<?php

namespace App\Controller\Admin;

use App\Entity\Deposit;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
            TextField::new('balance.currency', 'Currency')
                ->hideOnForm(),

            NumberField::new('amount')
                ->setNumDecimals(2),
            NumberField::new('expected_profit')
                ->setNumDecimals(2)
                ->onlyOnDetail(),

            FormField::addFieldset()
                ->onlyOnDetail(),
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->setNumDecimals(2)
                ->hideOnForm(),
            NumberField::new('expected_profit_in_usd', 'Expected Profit in USD')
                ->setNumDecimals(2)
                ->hideOnForm(),

            FormField::addColumn(4),
            FormField::addFieldset(),
            NumberField::new('interest')
                ->formatValue(function ($value) {
                    return $value . '%';
                }),
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
