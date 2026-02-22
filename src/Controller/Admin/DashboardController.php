<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Balance;
use App\Entity\Configuration;
use App\Entity\Currency;
use App\Entity\Deposit;
use App\Entity\Exchange;
use App\Entity\Income;
use App\Entity\Investment;
use App\Entity\Loan;
use App\Entity\Tag;
use App\Entity\Payment;
use App\Repository\BalanceRepository;
use App\Repository\CurrencyRepository;
use App\Repository\DepositRepository;
use App\Repository\IncomeRepository;
use App\Repository\InvestmentRepository;
use App\Repository\LoanRepository;
use App\Repository\PaymentRepository;
use App\Utils\PriceUtils;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private const CHART_MIN_NUMBER_OF_DAYS = 7;

    public function __construct(
        private readonly BalanceRepository $balanceRepository,
        private readonly CurrencyRepository $currencyRepository,
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly DepositRepository $depositRepository,
        private readonly IncomeRepository $incomeRepository,
        private readonly InvestmentRepository $investmentRepository,
        private readonly LoanRepository $loanRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public static function getCacheTag(Admin $admin): string
    {
        return 'dashboard_' . $admin->getId();
    }

    public function index(): Response
    {
        /** @var Admin $admin */
        $admin = $this->getUser();

        $incomesThisMonth = $this->getIncomesThisMonth($admin);
        $expensesThisMonth = $this->getExpensesThisMonth($admin);

        return $this->render('admin/index.html.twig', [
            'total' => PriceUtils::format($this->getGrandTotal($admin)),
            'incomes_this_month' => PriceUtils::format($incomesThisMonth),
            'expenses_this_month' => PriceUtils::format($expensesThisMonth),
            'diff_this_month' => PriceUtils::format($incomesThisMonth - $expensesThisMonth),
            'total_in_deposits' => PriceUtils::format($this->getTotalInDeposits($admin)),
            'total_in_investments' => PriceUtils::format($this->getTotalInInvestments($admin)),
            'total_in_loans' => PriceUtils::format($this->getTotalInLoans($admin)),
            'main_charts' => [
                [
                    'id' => 'this_month',
                    'title' => 'This Month',
                    'chart'=> $this->getTotalsChart('first day of this month', 'day', $admin),
                ],
                [
                    'id' => 'last_6_months',
                    'title' => 'Last 6 Months',
                    'chart'=> $this->getTotalsChart('first day of 6 months ago', 'week', $admin),
                ],
                [
                    'id' => 'months_by_diff',
                    'title' => 'Months By Diff',
                    'chart'=> $this->getMonthDiffChart($admin),
                ],
            ],
            'assets_by_balance_chart' => $this->getAssetsByBalanceChart($admin),
            'assets_by_currency_chart' => $this->getAssetsByCurrencyChart($admin),
            'expenses_by_tag_chart' => $this->getExpensesByTagChart($admin),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Personal Accountant')
        ;
    }

    /**
     * @see https://fontawesome.com/search?q=money&o=r&m=free
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Balance', 'fas fa-coins', Balance::class);
        yield MenuItem::linkToRoute('Analyze', 'fas fa-chart-simple', 'admin_analyze');
        yield MenuItem::section('Transactions');
        yield MenuItem::linkToCrud('Income', 'fas fa-money-bill-trend-up', Income::class);
        yield MenuItem::linkToCrud('Payment', 'fas fa-money-bill', Payment::class);
        yield MenuItem::linkToCrud('Exchange', 'fas fa-money-bill-transfer', Exchange::class);
        yield MenuItem::section('Savings');
        yield MenuItem::linkToCrud('Deposit', 'fas fa-percent', Deposit::class)
            ->setQueryParameter('filters[status][comparison]', '=')
            ->setQueryParameter('filters[status][value]', Deposit::STATUS_ACTIVE);
        yield MenuItem::linkToCrud('Investment', 'fas fa-arrow-trend-up', Investment::class);
        yield MenuItem::linkToCrud('Loan', 'fas fa-sack-dollar', Loan::class);
        yield MenuItem::section();
        yield MenuItem::linkToCrud('Currency', 'fas fa-dollar', Currency::class);
        yield MenuItem::linkToCrud('Tag', 'fas fa-tag', Tag::class);
        yield MenuItem::section();
        yield MenuItem::linkToCrud('Configuration', 'fas fa-gear', Configuration::class);
        yield MenuItem::linkToRoute('Applications', 'fas fa-calculator', 'admin_apps');
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
        ;
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->showEntityActionsInlined()
        ;
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addAssetMapperEntry('app')
        ;
    }

    private function getGrandTotal(Admin $admin): float
    {
        $balances = $this->balanceRepository->findByAdmin($admin);
        $depositTotal = $this->getTotalInDeposits($admin);
        $total = 0;

        foreach ($balances as $balance) {
            $total += $balance->getAmountInUsd();
        }

        return $total + $depositTotal;
    }

    private function getTotalInDeposits(Admin $admin): float
    {
        $deposits = $this->depositRepository->findAllActive($admin);
        $totalInDeposits = 0;

        foreach ($deposits as $deposit) {
            $totalInDeposits += $deposit->getAmountInUsd();
        }

        return $totalInDeposits;
    }

    private function getTotalInLoans(Admin $admin): float
    {
        $loans = $this->loanRepository->findByAdmin($admin);
        $totalInLoans = 0;

        foreach ($loans as $loan) {
            $totalInLoans += $loan->getAmountInUsd();
        }

        return $totalInLoans;
    }

    private function getTotalInInvestments(Admin $admin): float
    {
        $investments = $this->investmentRepository->findByAdmin($admin);
        $totalInInvestments = 0;

        foreach ($investments as $investment) {
            $totalInInvestments += $investment->getValue();
        }

        return $totalInInvestments;
    }

    private function getIncomesThisMonth(Admin $admin): float
    {
        $day = new \DateTime('first day of this month 00:00:00');
        $incomes = $this->incomeRepository->findAfterDate($day, $admin);
        $incomesThisMonth = 0;

        foreach ($incomes as $income) {
            $incomesThisMonth += $income->getAmountInUsd();
        }

        return $incomesThisMonth;
    }

    private function getExpensesThisMonth(Admin $admin): float
    {
        $day = new \DateTime('first day of this month 00:00:00');
        $payments = $this->paymentRepository->findAfterDate($day, $admin);
        $expensesThisMonth = 0;

        foreach ($payments as $payment) {
            $expensesThisMonth += $payment->getAmountInUsd();
        }

        return $expensesThisMonth;
    }

    private function getTotalsChart(string $startDay, string $step, Admin $admin): Chart
    {
        $now = (new \DateTime())->format('j');
        $userId = $admin->getId();

        $records = $this->cache->get(
            'totals_' . $userId . '_' . str_replace(' ', '_', $startDay) . '+' . $step . '__' . $now,
            function (ItemInterface $item) use ($startDay, $step, $admin) {
                $item->expiresAfter(86400);
                $item->tag(self::getCacheTag($admin));

                $balances = $this->balanceRepository->findByAdmin($admin);
                $depositTotal = $this->getTotalInDeposits($admin);

                $day = new \DateTime("$startDay 00:00:00");
                $today = new \DateTime();
                $computedValue = [];

                if ($day->diff($today)->days < self::CHART_MIN_NUMBER_OF_DAYS) {
                    $day = new \DateTime(self::CHART_MIN_NUMBER_OF_DAYS . ' days ago 00:00:00');
                }

                while ($day <= $today) {
                    $total = 0;

                    foreach ($balances as $balance) {
                        $total += $balance->getAmountInUsdAtMoment($day);
                    }

                    $computedValue[$day->format('d/m')] = $total + $depositTotal;
                    $day->modify("+1 $step");
                }

                return $computedValue;
            }
        );

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => array_keys($records),
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => array_map(fn($record) => number_format($record, 2, '.', ''), array_values($records)),
                ],
            ],
        ]);

        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ]);

        return $chart;
    }

    private function getMonthDiffChart(Admin $admin): Chart
    {
        $now = (new \DateTime())->format('j');
        $userId = $admin->getId();

        $records = $this->cache->get('months_by_diff_' . $userId . '__' . $now, function (ItemInterface $item) use ($admin) {
            $item->expiresAfter(86400);
            $item->tag(self::getCacheTag($admin));

            $balances = $this->balanceRepository->findByAdmin($admin);

            $day = new \DateTime('first day of 6 months ago 00:00:00');
            $today = new \DateTime();
            $computedValue = [];

            while ($day <= $today) {
                $totalStart = 0;
                $totalEnd = 0;

                $dayEnd = clone $day;
                $dayEnd->modify('+1 month');

                foreach ($balances as $balance) {
                    $totalStart += $balance->getAmountInUsdAtMoment($day);
                    $totalEnd += $balance->getAmountInUsdAtMoment($dayEnd);
                }

                $computedValue[$day->format('m/y')] = $totalEnd - $totalStart;
                $day = $dayEnd;
            }

            return $computedValue;
        });

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_keys($records),
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => array_map(fn($record) => number_format($record, 2, '.', ''), array_values($records)),
                ],
            ],
        ]);

        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ]);

        return $chart;
    }

    private function getAssetsByBalanceChart(Admin $admin): Chart
    {
        $now = (new \DateTime())->format('j');
        $userId = $admin->getId();

        $data = $this->cache->get('assets_by_balance_' . $userId . '__' . $now, function (ItemInterface $item) use ($admin) {
            $item->expiresAfter(86400);
            $item->tag(self::getCacheTag($admin));

            $balances = $this->balanceRepository->findByAdmin($admin);

            $data = array_map(function (Balance $balance) {
                return [
                    'label' => (string) $balance,
                    'value' => number_format($balance->getAmountInUsd(), 2, '.', ''),
                ];
            }, $balances);

            usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

            return $data;
        });

        return $this->getDoughnutChart($data, 'Assets by balance (in USD)');
    }

    private function getAssetsByCurrencyChart(Admin $admin): Chart
    {
        $now = (new \DateTime())->format('j');
        $userId = $admin->getId();

        $data = $this->cache->get('assets_by_currency_' . $userId . '__' . $now, function (ItemInterface $item) use ($admin) {
            $item->expiresAfter(86400);
            $item->tag(self::getCacheTag($admin));

            $currencies = $this->currencyRepository->findBy(['admin' => $admin]);

            $data = array_map(function (Currency $currency) {
                $balances = $currency->getBalances();
                $total = 0;

                foreach ($balances as $balance) {
                    $total += $balance->getAmountInUsd();
                }

                return [
                    'label' => $currency->getCode(),
                    'value' => number_format($total, 2, '.', ''),
                ];
            }, $currencies);

            usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

            return $data;
        });

        return $this->getDoughnutChart($data, 'Assets by currency (in USD)');
    }

    private function getExpensesByTagChart(Admin $admin): Chart
    {
        $now = (new \DateTime())->format('j');
        $userId = $admin->getId();

        $data = $this->cache->get('expenses_by_tag_' . $userId . '__' . $now, function (ItemInterface $item) use ($admin) {
            $item->expiresAfter(86400);
            $item->tag(self::getCacheTag($admin));

            $dateFrom = new \DateTime('first day of 6 months ago 00:00:00');

            $payments = $this->paymentRepository->findAfterDate($dateFrom, $admin);
            $totalExpenses = 0;
            $tags = [];

            foreach ($payments as $payment) {
                $totalExpenses = $totalExpenses + $payment->getAmountInUsd();

                $tag = $payment->getTag();
                $tagId = $tag->getId();

                if (!isset($tags[$tagId])) {
                    $tags[$tagId] = [
                        'name' => $tag->getName(),
                        'total' => 0,
                    ];
                }

                $tags[$tagId]['total'] = $tags[$tagId]['total'] + $payment->getAmountInUsd();
            }

            if (!$totalExpenses) {
                return [];
            }

            $data = array_map(function ($tag) use ($totalExpenses) {
                return [
                    'label' => $tag['name'],
                    'value' => number_format($tag['total'] / $totalExpenses * 100, 1, '.', ''),
                ];
            }, $tags);

            usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

            return $data;
        });

        return $this->getDoughnutChart($data, 'Expenses last 6 months (in %)');
    }

    private function getDoughnutChart(array $data, string $title): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => array_map(fn($item) => $item['label'], $data),
            'datasets' => [
                [
                    'data' => array_map(fn($item) => $item['value'], $data),
                ],
            ],
        ]);

        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'title' => [
                    'display' => true,
                    'text' => $title,
                ],
                'autocolors' => [
                    'mode' => 'data',
                ],
            ],
        ]);

        return $chart;
    }
}
