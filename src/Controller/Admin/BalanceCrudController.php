<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BalanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Balance::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            FormField::addFieldset(),
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            AssociationField::new('currency'),
            NumberField::new('amount')
                ->setNumDecimals(2),
            NumberField::new('amount_in_usd')
                ->setNumDecimals(2)
                ->hideOnForm()
                ->setLabel('Amount in USD'),

            FormField::addFieldset()
                ->onlyOnDetail(),
            CollectionField::new('transactions')
                ->setTemplatePath('admin/fields/transactions_by_balance.html.twig')
                ->onlyOnDetail(),
            CollectionField::new('exchanges_from')
                ->setTemplatePath('admin/fields/exchanges_by_balance_from.html.twig')
                ->onlyOnDetail(),
            CollectionField::new('exchanges_to')
                ->setTemplatePath('admin/fields/exchanges_by_balance_to.html.twig')
                ->onlyOnDetail(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('currency');
    }
}
