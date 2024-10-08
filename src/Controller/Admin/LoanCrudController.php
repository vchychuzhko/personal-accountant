<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
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

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $iqb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $sortFields = $searchDto->getSort();

        if (isset($sortFields['amount_in_usd'])) {
            $sortDirection = $sortFields['amount_in_usd'];

            $iqb->leftJoin('entity.currency', 'currency')
                ->addSelect('(entity.amount / currency.rate) AS HIDDEN amount_in_usd')
                ->orderBy('amount_in_usd', $sortDirection);
        }

        return $iqb;
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
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->setNumDecimals(2)
                ->setSortable(true)
                ->hideOnForm(),
            DateField::new('created_at')
                ->setFormat('dd-MM-yyyy'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('currency');
    }
}
