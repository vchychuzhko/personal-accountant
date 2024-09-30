<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LoanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Loan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('person'),
            AssociationField::new('currency'),
            NumberField::new('amount')
                ->setNumDecimals(2),
            NumberField::new('amount_in_usd')
                ->setNumDecimals(2)
                ->hideOnForm()
                ->setLabel('Amount in USD'),
            DateField::new('created_at')
                ->setFormat('dd-MM-yyyy'),
        ];
    }
}
