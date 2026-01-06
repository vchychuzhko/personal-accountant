<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Utils\PriceUtils;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
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

            $qb->leftJoin('entity.currency', 'currency')
                ->addSelect('(entity.amount / currency.rate) AS HIDDEN amount_in_usd')
                ->orderBy('amount_in_usd', $sortDirection);
        }

        return $qb;
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
                ->formatValue(function ($value, Balance $entity) {
                    $currency = $entity->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->setSortable(true)
                ->hideOnForm(),

            FormField::addFieldset()
                ->onlyOnDetail(),
            ArrayField::new('incomes')
                ->setTemplatePath('admin/fields/incomes_by_balance.html.twig')
                ->onlyOnDetail(),
            ArrayField::new('payments')
                ->setTemplatePath('admin/fields/payments_by_balance.html.twig')
                ->onlyOnDetail(),
            ArrayField::new('exchanges_from')
                ->setTemplatePath('admin/fields/exchanges_by_balance_from.html.twig')
                ->onlyOnDetail(),
            ArrayField::new('exchanges_to')
                ->setTemplatePath('admin/fields/exchanges_by_balance_to.html.twig')
                ->onlyOnDetail(),
            ArrayField::new('active_deposits', 'Deposits')
                ->setTemplatePath('admin/fields/deposits_by_balance.html.twig')
                ->onlyOnDetail(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('currency');
    }
}
