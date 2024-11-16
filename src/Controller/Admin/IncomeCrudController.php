<?php

namespace App\Controller\Admin;

use App\Entity\Income;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class IncomeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Income::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            AssociationField::new('balance'),
            NumberField::new('amount')
                ->formatValue(function ($value, Income $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->hideOnForm(),
            DateTimeField::new('created_at')
                ->setFormat('dd-MM-yyyy HH:mm'),
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
            ->setDefaultSort(['created_at' => 'DESC']);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Income $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $balance = $entityInstance->getBalance();

        $balance->setAmount($balance->getAmount() + $entityInstance->getAmount());

        $entityManager->persist($balance);
        $entityManager->flush();

        parent::persistEntity($entityManager, $entityInstance);
    }
}
