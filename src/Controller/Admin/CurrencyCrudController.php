<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CurrencyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Currency::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            FormField::addFieldset(),
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            NumberField::new('rate'),

            FormField::addFieldset()
                ->onlyOnDetail(),
            CollectionField::new('balances')
                ->setTemplatePath('admin/fields/balances_by_currency.html.twig')
                ->hideOnForm(),
            CollectionField::new('deposits')
                ->setTemplatePath('admin/fields/deposits_by_currency.html.twig')
                ->hideOnForm(),
            CollectionField::new('Loans')
                ->setTemplatePath('admin/fields/loans_by_currency.html.twig')
                ->hideOnForm(),
        ];
    }
}
