<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Entity\Configuration;
use App\Entity\Currency;
use App\Entity\Deposit;
use App\Entity\Exchange;
use App\Entity\Income;
use App\Entity\Loan;
use App\Entity\Tag;
use App\Entity\Payment;
use App\Repository\BalanceRepository;
use App\Repository\CurrencyRepository;
use App\Repository\DepositRepository;
use App\Repository\IncomeRepository;
use App\Repository\LoanRepository;
use App\Repository\PaymentRepository;
use App\Repository\TagRepository;
use App\Utils\PriceUtils;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public const DASHBOARD_CACHE_TAG = 'dashboard';

    public function __construct(
        private readonly BalanceRepository $balanceRepository,
        private readonly CurrencyRepository $currencyRepository,
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly DepositRepository $depositRepository,
        private readonly IncomeRepository $incomeRepository,
        private readonly LoanRepository $loanRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TagRepository $tagRepository,
        private readonly TagAwareCacheInterface $cache
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $incomesThisMonth = $this->getIncomesThisMonth();
        $expensesThisMonth = $this->getExpensesThisMonth();

        return $this->render('admin/index.html.twig', [
            'total' => PriceUtils::format($this->getGrandTotal()),
            'incomes_this_month' => PriceUtils::format($incomesThisMonth),
            'expenses_this_month' => PriceUtils::format($expensesThisMonth),
            'diff_this_month' => PriceUtils::format($incomesThisMonth - $expensesThisMonth),
            'total_in_deposits' => PriceUtils::format($this->getTotalInDeposits()),
            'total_in_loans' => PriceUtils::format($this->getTotalInLoans()),
            'main_charts' => [
                [
                    'id' => 'this_month',
                    'title' => 'This Month',
                    'chart'=> $this->getTotalsChart('first day of this month', 'day'),
                ],
                [
                    'id' => 'last_6_months',
                    'title' => 'Last 6 Months',
                    'chart'=> $this->getTotalsChart('first day of 6 months ago', 'week'),
                ],
            ],
            'assets_by_balance_chart' => $this->getAssetsByBalanceChart(),
            'assets_by_currency_chart' => $this->getAssetsByCurrencyChart(),
            'expenses_by_tag_chart' => $this->getExpensesByTagChart(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Personal Accountant');
    }

    /**
     * @see https://fontawesome.com/search?q=money&o=r&m=free
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Balance', 'fas fa-coins', Balance::class);
        yield MenuItem::section();
        yield MenuItem::linkToCrud('Income', 'fas fa-money-bill-trend-up', Income::class);
        yield MenuItem::linkToCrud('Payment', 'fas fa-money-bill', Payment::class);
        yield MenuItem::linkToCrud('Exchange', 'fas fa-hand-holding-dollar', Exchange::class);
        yield MenuItem::section();
        yield MenuItem::linkToCrud('Deposit', 'fas fa-percent', Deposit::class);
        yield MenuItem::linkToCrud('Loan', 'fas fa-sack-dollar', Loan::class);
        yield MenuItem::linkToCrud('Tag', 'fas fa-tag', Tag::class);
        yield MenuItem::linkToCrud('Currency', 'fas fa-dollar', Currency::class);
        yield MenuItem::section();
        yield MenuItem::linkToCrud('Configuration', 'fas fa-gear', Configuration::class);
        yield MenuItem::linkToRoute('Apps', 'fas fa-calculator', 'app_admin_apps');
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->showEntityActionsInlined();
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addAssetMapperEntry('app');
    }

    private function getGrandTotal(): float
    {
        $balances = $this->balanceRepository->findAll();
        $total = 0;

        foreach ($balances as $balance) {
            $total += $balance->getAmountInUsd();
        }

        return $total;
    }

    private function getTotalInLoans(): float
    {
        $loans = $this->loanRepository->findAll();
        $totalInLoans = 0;

        foreach ($loans as $loan) {
            $totalInLoans += $loan->getAmountInUsd();
        }

        return $totalInLoans;
    }

    private function getTotalInDeposits(): float
    {
        $deposits = $this->depositRepository->findAllActive();
        $totalInDeposits = 0;

        foreach ($deposits as $deposit) {
            $totalInDeposits += $deposit->getAmountInUsd();
        }

        return $totalInDeposits;
    }

    private function getIncomesThisMonth(): float
    {
        $day = new \DateTime('first day of this month 00:00:00');
        $incomes = $this->incomeRepository->findAfterDate($day);
        $incomesThisMonth = 0;

        foreach ($incomes as $income) {
            $incomesThisMonth += $income->getAmountInUsd();
        }

        return $incomesThisMonth;
    }

    private function getExpensesThisMonth(): float
    {
        $day = new \DateTime('first day of this month 00:00:00');
        $payments = $this->paymentRepository->findAfterDate($day);
        $expensesThisMonth = 0;

        foreach ($payments as $payment) {
            $expensesThisMonth += $payment->getAmountInUsd();
        }

        return $expensesThisMonth;
    }

    private function getTotalsChart(string $startDay, string $step): Chart
    {
        $records = $this->cache->get("$startDay +1 $step", function (ItemInterface $item) use ($startDay, $step) {
            $item->expiresAfter(86400);
            $item->tag(self::DASHBOARD_CACHE_TAG);

            $balances = $this->balanceRepository->findAll();

            $day = new \DateTime("$startDay 00:00:00");
            $today = new \DateTime();
            $computedValue = [];

            while ($day <= $today) {
                $total = 0;

                foreach ($balances as $balance) {
                    $total += $balance->getAmountInUsdAtMoment($day);
                }

                $computedValue[$day->format('d/m')] = $total;
                $day->modify("+1 $step");
            }

            return $computedValue;
        });

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

    private function getAssetsByBalanceChart(): Chart
    {
        $data = $this->cache->get('Assets by balance', function (ItemInterface $item) {
            $item->expiresAfter(86400);
            $item->tag(self::DASHBOARD_CACHE_TAG);

            $balances = $this->balanceRepository->findAll();
            $chart1 = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
            $chart1->setData([
                'labels' => array_map(fn(Balance $balance) => $balance->__toString(), $balances),
                'datasets' => [
                    [
                        'data' => array_map(fn(Balance $balance) => number_format($balance->getAmountInUsd(), 2, '.', ''), $balances),
                    ],
                ],
            ]);

            $data = array_map(function (Balance $balance) {
                return [
                    'label' => (string) $balance,
                    'value' => number_format($balance->getAmountInUsd(), 2, '.', ''),
                ];
            }, $balances);

            usort($data, fn ($a, $b) => $b['value'] <=> $a['value']);

            return $data;
        });

        return $this->getDoughnutChart($data, 'Assets by balance (in USD)');
    }

    private function getAssetsByCurrencyChart(): Chart
    {
        $data = $this->cache->get('Assets by currency', function (ItemInterface $item) {
            $item->expiresAfter(86400);
            $item->tag(self::DASHBOARD_CACHE_TAG);

            $currencies = $this->currencyRepository->findAll();

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

            usort($data, fn ($a, $b) => $b['value'] <=> $a['value']);

            return $data;
        });

        return $this->getDoughnutChart($data, 'Assets by currency (in USD)');
    }

    private function getExpensesByTagChart(): Chart
    {
        $data = $this->cache->get('Expenses by tag', function (ItemInterface $item) {
            $item->expiresAfter(86400);
            $item->tag(self::DASHBOARD_CACHE_TAG);

            $tags = $this->tagRepository->findAll();
            $payments = $this->paymentRepository->findAll();
            $totalExpenses = 0;

            foreach ($payments as $payment) {
                $totalExpenses = $totalExpenses + $payment->getAmountInUsd();
            }

            $data = array_map(function (Tag $tag) use ($totalExpenses) {
                $payments = $tag->getPayments();
                $total = 0;

                foreach ($payments as $payment) {
                    $total += $payment->getAmountInUsd();
                }

                return [
                    'label' => $tag->getName(),
                    'value' => number_format($total / $totalExpenses * 100, 1, '.', ''),
                ];
            }, $tags);

            usort($data, fn ($a, $b) => $b['value'] <=> $a['value']);

            return $data;
        });

        return $this->getDoughnutChart($data, 'Expenses by tag (in %)');
    }

    private function getDoughnutChart(array $data, string $title): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => array_map(fn ($item) => $item['label'], $data),
            'datasets' => [
                [
                    'data' => array_map(fn ($item) => $item['value'], $data),
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
