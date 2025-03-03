<?php

namespace App\Controller\Admin;

use App\Entity\Deposit;
use App\Entity\Income;
use App\Utils\PriceUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DepositCrudController extends AbstractCrudController
{
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
            FormField::addColumn(8)
                ->addCssClass('form-column--wide'),
            FormField::addFieldset(),
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            AssociationField::new('balance'),

            NumberField::new('amount')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                }),
            NumberField::new('expected_profit')
                ->formatValue(function ($value, Deposit $entity) {
                    $currency = $entity->getBalance()->getCurrency();

                    return PriceUtils::format($value, $currency->getFormat());
                })
                ->onlyOnDetail(),
            NumberField::new('profit')
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
            NumberField::new('expected_profit_in_usd', 'Expected Profit in USD')
                ->formatValue(function ($value) {
                    return PriceUtils::format($value);
                })
                ->hideOnForm(),
            NumberField::new('profit_in_usd', 'Profit in USD')
                ->formatValue(function ($value) {
                    return $value ? PriceUtils::format($value): $value;
                })
                ->hideOnForm(),

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
                ->setFormat('dd-MM-yyyy')
                ->hideOnIndex(),
            DateField::new('end_date')
                ->setFormat('dd-MM-yyyy'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('balance')
            ->add(
                ChoiceFilter::new('status')
                    ->setChoices(array_flip(Deposit::STATUS_MAP))
            )
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $complete = Action::new('complete')
            ->linkToCrudAction('complete')
            ->setTemplatePath('admin/deposit_complete_action.html.twig')
            ->displayIf(fn (Deposit $deposit) => $deposit->isActive());

        return parent::configureActions($actions)
            ->add(Crud::PAGE_DETAIL, $complete);
    }

    #[AdminAction(routePath: '/complete', routeName: 'deposit_complete', methods: ['POST'])]
    public function complete(
        AdminContext $adminContext,
        EntityManagerInterface $entityManager,
        Request $request,
        TagAwareCacheInterface $cache,
        AdminUrlGenerator $adminUrlGenerator,
    ): RedirectResponse {
        /** @var Deposit $deposit */
        $deposit = $adminContext->getEntity()->getInstance();

        $profit = (float) $request->get('profit');

        $income = new Income();
        $income
            ->setName('Yield from "' . $deposit->getName() . '" (#' . $deposit->getId() . ')')
            ->setBalance($deposit->getBalance())
            ->setAmount($profit);
        $entityManager->persist($income);

        $balance = $deposit->getBalance();
        $balance->setAmount($balance->getAmount() + $deposit->getAmount() + $profit);
        $entityManager->persist($balance);

        $deposit->setStatus(Deposit::STATUS_COMPLETED);
        $deposit->setProfit($profit);
        $entityManager->persist($deposit);

        $entityManager->flush();

        $cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $this->addFlash('success', 'Deposit "' . $deposit->getName() . '" is completed');

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
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

        parent::persistEntity($entityManager, $entityInstance);
    }
}
