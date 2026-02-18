<?php

namespace App\Controller\Admin;

use App\Entity\Exchange;
use App\Repository\ConfigurationRepository;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ExchangeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Exchange::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $timezone = $this->configurationRepository->getByName('timezone');

        return [
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
                ->formatValue(fn($value) => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.'))
                ->hideOnForm(),
            DateTimeField::new('created_at')
                ->setFormTypeOption('model_timezone', 'UTC')
                ->setFormTypeOption('view_timezone', $timezone)
                ->setFormat('dd.MM.yyyy HH:mm')
                ->setTimezone($timezone)
                ->setHelp($timezone),
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
            ->setDefaultSort(['created_at' => 'DESC'])
        ;
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

        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        parent::persistEntity($entityManager, $entityInstance);
    }
}
