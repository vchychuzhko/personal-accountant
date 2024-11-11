<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Entity\Currency;
use App\Entity\Deposit;
use App\Entity\Exchange;
use App\Entity\Income;
use App\Entity\Loan;
use App\Entity\Tag;
use App\Entity\Payment;
use App\Repository\BalanceRepository;
use App\Repository\LoanRepository;
use App\Repository\PaymentRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly BalanceRepository $balanceRepository,
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly LoanRepository $loanRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly CacheInterface $cache
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $incomesThisMonth = $this->getIncomesThisMonth();
        $expensesThisMonth = $this->getExpensesThisMonth();

        return $this->render('admin/index.html.twig', [
            'total' => number_format($this->getGrandTotal(), 2, '.', ','),
            'incomes_this_month' => number_format($incomesThisMonth, 2, '.', ','),
            'expenses_this_month' => number_format($expensesThisMonth, 2, '.', ','),
            'diff_this_month' => number_format($incomesThisMonth - $expensesThisMonth, 2, '.', ','),
            'total_in_deposits' => number_format($this->getTotalInDeposits(), 2, '.', ','),
            'expected_deposit_profit' => number_format($this->getExpectedDepositsProfit(), 2, '.', ','),
            'total_in_loans' => number_format($this->getTotalInLoans(), 2, '.', ','),
            'chart' => $this->getMainChart(),
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
        yield MenuItem::section('Transactions');
        yield MenuItem::linkToCrud('Income', 'fas fa-money-bill-trend-up', Income::class);
        yield MenuItem::linkToCrud('Payment', 'fas fa-money-bill', Payment::class);
        yield MenuItem::linkToCrud('Exchange', 'fas fa-hand-holding-dollar', Exchange::class);
        yield MenuItem::section();
        yield MenuItem::linkToCrud('Deposit', 'fas fa-percent', Deposit::class);
        yield MenuItem::linkToCrud('Loan', 'fas fa-sack-dollar', Loan::class);
        yield MenuItem::linkToCrud('Tag', 'fas fa-tag', Tag::class);
        yield MenuItem::linkToCrud('Currency', 'fas fa-dollar', Currency::class);
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
        $deposits = $this->depositRepository->findAll();
        $totalInDeposits = 0;

        foreach ($deposits as $deposit) {
            $totalInDeposits += $deposit->getAmountInUsd();
        }

        return $totalInDeposits;
    }

    private function getExpectedDepositsProfit(): float
    {
        $deposits = $this->depositRepository->findAll();
        $totalDepositProfit = 0;

        foreach ($deposits as $deposit) {
            $totalDepositProfit += $deposit->getExpectedProfitInUsd();
        }

        return $totalDepositProfit;
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

    private function getMainChart(): Chart
    {
        $balances = $this->balanceRepository->findAll();

        $records = $this->cache->get('this_month', function (ItemInterface $item) use ($balances) {
            $item->expiresAfter(86400);

            $day = new \DateTime('first day of this month 00:00:00');
            $today = new \DateTime();
            $computedValue = [];

            while ($day <= $today) {
                $total = 0;

                foreach ($balances as $balance) {
                    $total += $balance->getAmountInUsdAtMoment($day);
                }

                $computedValue[$day->format('d/m')] = $total;
                $day->modify('+1 day');
            }

            return $computedValue;
        });

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => array_keys($records),
            'datasets' => [
                [
                    'label' => 'This month',
                    'data' => array_map(fn($record) => number_format($record, 2, '.', ''), array_values($records)),
                ],
            ],
        ]);

        $chart->setOptions([
            'plugins' => [
                'autocolors' => [
                    'mode' => 'data',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Dynamic by month (in USD)',
                ],
                'legend' => [
                    'display' => false,
                ],
            ],
        ]);

        return $chart;
    }
}
