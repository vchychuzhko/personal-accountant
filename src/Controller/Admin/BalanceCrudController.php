<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Entity\Currency;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BalanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Balance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('currency'),
            NumberField::new('amount')
                ->formatValue(function ($value) {
                    return number_format($value, 2, '.', '');
                }),
            NumberField::new('amount')
                ->formatValue(function ($value, $balance) {
                    /** @var Currency $currency */
                    $currency = $balance->getCurrency();

                    return number_format($value / $currency->getRate(), 2, '.', '');
                })
                ->hideOnForm()
                ->setLabel('Amount in USD'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('currency');
    }
}
