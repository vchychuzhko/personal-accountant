<?php

namespace App\Controller\Admin;

use App\Entity\Exchange;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class ExchangeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Exchange::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            AssociationField::new('balance_from'),
            AssociationField::new('balance_to'),
            NumberField::new('amount')
                ->formatValue(function ($value, Exchange $entity) {
                    $currency = $entity->getBalanceFrom()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('result')
                ->formatValue(function ($value, Exchange $entity) {
                    $currency = $entity->getBalanceTo()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('rate')
                ->setNumDecimals(2)
                ->hideOnForm(),
            DateTimeField::new('created_at')
                ->setFormat('dd-MM-yyyy HH:mm'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('balance_from')
            ->add('balance_to')
            ->add('created_at')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['created_at' => 'DESC']);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Exchange $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $balanceFrom = $entityInstance->getBalanceFrom();
        $balanceTo = $entityInstance->getBalanceTo();

        $balanceFrom->setAmount($balanceFrom->getAmount() - $entityInstance->getAmount());
        $balanceTo->setAmount($balanceTo->getAmount() + $entityInstance->getResult());

        $entityManager->persist($balanceFrom);
        $entityManager->persist($balanceTo);
        $entityManager->flush();

        parent::persistEntity($entityManager, $entityInstance);
    }
}
