<?php

namespace App\Controller\Admin;

use App\Entity\Deposit;
use App\Entity\Income;
use App\Utils\PriceUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DepositCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Deposit::class;
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

            $qb->leftJoin('entity.balance', 'balance')
                ->leftJoin('balance.currency', 'currency')
                ->addSelect('(entity.amount / currency.rate) AS HIDDEN amount_in_usd')
                ->orderBy('amount_in_usd', $sortDirection);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(8),
            FormField::addFieldset(),
            TextField::new('name'),
            AssociationField::new('balance'),

            NumberField::new('amount')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('profit')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return $value ? PriceUtils::format($value, $currency->getFormat()) : $value;
                })
                ->onlyOnDetail(),
            NumberField::new('dividends')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return $value ? PriceUtils::format($value, $currency->getFormat()) : $value;
                })
                ->onlyOnDetail(),

            FormField::addFieldset()
                ->onlyOnDetail(),
            NumberField::new('amount_in_usd', 'Amount in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->setSortable(true)
                ->hideOnForm(),
            NumberField::new('profit_in_usd', 'Profit in USD')
                ->formatValue(function ($value) {
                    return $value ? PriceUtils::format($value) : $value;
                })
                ->hideOnForm(),
            NumberField::new('dividends_in_usd', 'Dividends in USD')
                ->formatValue(function ($value) {
                    return $value ? PriceUtils::format($value) : $value;
                })
                ->onlyOnDetail(),

            FormField::addFieldset('Incomes')
                ->addCssClass('form-fieldset--no-labels'),
            AssociationField::new('incomes') // edit for all incomes
                ->autocomplete()
                ->onlyOnForms(),
            AssociationField::new('incomes') // display only incomes
                ->setTemplatePath('admin/fields/incomes_by_deposit.html.twig')
                ->formatValue(function ($value) {
                    return $value->filter(fn(Income $income) => !$income->isDividend());
                })
                ->onlyOnDetail(),

            FormField::addFieldset('Dividends')
                ->addCssClass('form-fieldset--no-labels')
                ->onlyOnDetail(),
            AssociationField::new('incomes') // display only dividends
                ->setTemplatePath('admin/fields/incomes_by_deposit.html.twig')
                ->formatValue(function ($value) {
                    return $value->filter(fn(Income $income) => $income->isDividend());
                })
                ->onlyOnDetail(),

            FormField::addColumn(4),
            FormField::addFieldset(),
            ChoiceField::new('status')
                ->setChoices(array_flip(Deposit::STATUS_MAP))
                ->setTemplatePath('admin/fields/status.html.twig'),
            PercentField::new('interest')
                ->setStoredAsFractional(false)
                ->setRoundingMode(\NumberFormatter::ROUND_CEILING)
                ->setNumDecimals(2),
            PercentField::new('tax')
                ->setStoredAsFractional(false)
                ->setRoundingMode(\NumberFormatter::ROUND_CEILING)
                ->setNumDecimals(2)
                ->hideOnIndex(),
            NumberField::new('period')
                ->formatValue(function ($value) {
                    return $value . ' ' . ($value === 1 ? 'month' : ' months');
                })
                ->setHelp('Number of months')
                ->hideOnIndex(),
            DateField::new('start_date')
                ->setFormat('dd.MM.yyyy')
                ->hideOnIndex(),
            DateField::new('end_date')
                ->setFormat('dd.MM.yyyy'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(
                EntityFilter::new('balance')
                    ->canSelectMultiple()
            )
            ->add(
                ChoiceFilter::new('status')
                    ->setChoices(array_flip(Deposit::STATUS_MAP))
            )
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['end_date' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $complete = Action::new('complete')
            ->linkToCrudAction('complete')
            ->setTemplatePath('admin/actions/deposit_complete.html.twig')
            ->displayIf(fn(Deposit $deposit) => $deposit->isActive());
        $cancel = Action::new('cancel')
            ->linkToCrudAction('cancel')
            ->setTemplatePath('admin/actions/deposit_cancel.html.twig')
            ->displayIf(fn(Deposit $deposit) => $deposit->isActive());

        return parent::configureActions($actions)
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action->displayIf(fn(Deposit $entity) => !$entity->isActive());
            })
            ->add(Crud::PAGE_DETAIL, $complete)
            ->add(Crud::PAGE_DETAIL, $cancel)
        ;
    }

    #[AdminRoute('/{id}/complete', options: ['methods' => ['POST']])]
    public function complete(Deposit $deposit, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('complete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $profit = (float) $request->request->get('profit');

        if ($profit > 0) {
            $income = new Income();
            $income
                ->setName('Yield from "' . $deposit->getName() . '" (#' . $deposit->getId() . ')')
                ->setBalance($deposit->getBalance())
                ->setDeposit($deposit)
                ->setAmount($profit);

            $this->entityManager->persist($income);
        }

        $balance = $deposit->getBalance();
        $balance->setAmount($balance->getAmount() + $deposit->getAmount() + $profit);
        $this->entityManager->persist($balance);

        $deposit->setStatus(Deposit::STATUS_COMPLETED);
        $deposit->setProfit($profit);
        $this->entityManager->persist($deposit);

        $this->entityManager->flush();

        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $this->addFlash('success', 'Deposit "' . $deposit->getName() . '" is completed');

        $targetUrl = $this->adminUrlGenerator
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($deposit->getId())
            ->generateUrl();

        return $this->redirect($targetUrl);
    }

    #[AdminRoute('/{id}/cancel', options: ['methods' => ['POST']])]
    public function cancel(Deposit $deposit, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cancel', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $balance = $deposit->getBalance();
        $balance->setAmount($balance->getAmount() + $deposit->getAmount());
        $this->entityManager->persist($balance);

        $deposit->setStatus(Deposit::STATUS_CANCELED);
        $deposit->setProfit(0);
        $this->entityManager->persist($deposit);

        $this->entityManager->flush();

        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $this->addFlash('success', 'Deposit "' . $deposit->getName() . '" is canceled');

        $targetUrl = $this->adminUrlGenerator
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($deposit->getId())
            ->generateUrl();

        return $this->redirect($targetUrl);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Deposit $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $balance = $entityInstance->getBalance();

        $balance->setAmount($balance->getAmount() - $entityInstance->getAmount());

        $entityManager->persist($balance);
        $entityManager->flush();

        $this->syncIncomes($entityManager, $entityInstance);

        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Deposit $entityInstance
     * @return void
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->syncIncomes($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function syncIncomes(EntityManagerInterface $entityManager, Deposit $entityInstance): void
    {
        $currentIncomes = new ArrayCollection($entityInstance->getIncomes()->toArray());

        $existingIncomes = $entityManager->getRepository(Income::class)->findBy(['deposit' => $entityInstance]);

        // Detach removed
        foreach ($existingIncomes as $income) {
            if (!$currentIncomes->contains($income)) {
                $income->setDeposit(null);
                $entityManager->persist($income);
            }
        }

        // Attach added
        foreach ($currentIncomes as $income) {
            $income->setDeposit($entityInstance);
            $entityManager->persist($income);
        }
    }
}
