<?php

namespace App\Controller\Admin;

use App\Entity\Deposit;
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
            FormField::addColumn(8),
            FormField::addFieldset(),
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            AssociationField::new('currency'),

            NumberField::new('amount')
                ->setNumDecimals(2)
        ,
            NumberField::new('expected_profit')
                ->setNumDecimals(2)
                ->hideOnForm()
                ->setLabel('Expected profit'),
            NumberField::new('expected_total')
                ->setNumDecimals(2)
                ->hideOnForm()
                ->setLabel('Expected total'),

            FormField::addFieldset()
                ->addCssClass('wide')
                ->onlyOnDetail(),
            NumberField::new('amount_in_usd')
                ->setNumDecimals(2)
                ->onlyOnDetail()
                ->setLabel('Amount in USD'),
            NumberField::new('expected_profit_in_usd')
                ->setNumDecimals(2)
                ->onlyOnDetail()
                ->setLabel('Expected profit in USD'),
            NumberField::new('expected_total_in_usd')
                ->setNumDecimals(2)
                ->onlyOnDetail()
                ->setLabel('Expected total in USD'),

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
}
