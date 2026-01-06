<?php

namespace App\Controller\Admin;

use App\Repository\CurrencyRepository;
use App\Repository\PaymentRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class AnalyzeController extends AbstractController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly CurrencyRepository $currencyRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    #[Route(path: '/admin/analyze', name: 'admin_analyze')]
    public function index(Request $request): Response
    {
        $sortBy = $request->get('sort', 'total');

        $thisMonth = (new \DateTime('first day of this month 00:00:00'))->format('F Y');
        $lastMonth = (new \DateTime('first day of last month 00:00:00'))->format('F Y');

        return $this->render('admin/analyze.html.twig', [
            'currencies' => $this->currencyRepository->findAll(),
            'sort_by' => $sortBy,
            'months' => [
                [
                    'id' => 'this_month',
                    'title' => 'This Month',
                    'total_expenses' => $this->getTotalExpensesByMonth($thisMonth),
                    'payments_count' => $this->getPaymentsCount($thisMonth),
                    'payments_url' => $this->getPaymentsUrlByMonth($thisMonth),
                    'tags' => $this->getExpensesByTagByMonth($thisMonth, $sortBy),
                    'expenses_by_tag_chart' => $this->getExpensesByTagByMonthChart($thisMonth),
                ],
                [
                    'id' => 'last_month',
                    'title' => $lastMonth,
                    'total_expenses' => $this->getTotalExpensesByMonth($lastMonth),
                    'payments_count' => $this->getPaymentsCount($lastMonth),
                    'payments_url' => $this->getPaymentsUrlByMonth($lastMonth),
                    'tags' => $this->getExpensesByTagByMonth($lastMonth, $sortBy),
                    'expenses_by_tag_chart' => $this->getExpensesByTagByMonthChart($lastMonth),
                ],
            ],
        ]);
    }

    private function getTotalExpensesByMonth(string $monthKey): float
    {
        $tags = $this->getExpensesByTagByMonth($monthKey);
        $total = 0;

        foreach ($tags as $tag) {
            $total = $total + $tag['total'];
        }

        return $total;
    }

    private function getPaymentsCount(string $monthKey): int
    {
        $dateFrom = new \DateTime("first day of $monthKey 00:00:00");
        $dateTo = clone $dateFrom;
        $dateTo = $dateTo->modify('+1 month');

        $payments = $this->paymentRepository->findBetweenDates($dateFrom, $dateTo);

        return count($payments);
    }

    private function getPaymentsUrlByMonth(string $monthKey): string
    {
        $dateFrom = new \DateTime("first day of $monthKey 00:00:00");
        $dateTo = clone $dateFrom;
        $dateTo = $dateTo->modify('+1 month');

        return $this->getPaymentsUrlByTagAndCreatedAt($dateFrom, $dateTo);
    }

    private function getExpensesByTagByMonth(string $monthKey, ?string $sortBy = null): array
    {
        $data = $this->cache->get(
            'expenses_by_tag__' . str_replace(' ', '_', $monthKey),
            function (ItemInterface $item) use ($monthKey) {
                $item->tag(DashboardController::DASHBOARD_CACHE_TAG);

                $dateFrom = new \DateTime("first day of $monthKey 00:00:00");
                $dateTo = clone $dateFrom;
                $dateTo = $dateTo->modify('+1 month');

                $payments = $this->paymentRepository->findBetweenDates($dateFrom, $dateTo);
                $data = [];

                foreach ($payments as $payment) {
                    $tag = $payment->getTag();
                    $tagId = $tag->getId();

                    if (!isset($data[$tagId])) {
                        $data[$tagId] = [
                            'id' => $tagId,
                            'name' => $tag->getName(),
                            'payments' => [],
                            'payments_url' => $this->getPaymentsUrlByTagAndCreatedAt($dateFrom, $dateTo, $tagId),
                            'total' => 0,
                        ];
                    }

                    $data[$tagId]['payments'][] = $payment;
                    $data[$tagId]['total'] = $data[$tagId]['total'] + $payment->getAmountInUsd();
                }

                return $data;
            }
        );

        if ($sortBy) {
            usort($data, fn($a, $b) => $b[$sortBy] <=> $a[$sortBy]);
        }

        foreach ($data as &$tag) {
            usort($tag['payments'], fn($a, $b) => $b->getAmountInUsd() <=> $a->getAmountInUsd());
        }

        return $data;
    }

    private function getPaymentsUrlByTagAndCreatedAt(\DateTime $dateFrom, \DateTime $dateTo, int $tagId = null): string
    {
        $url = $this->adminUrlGenerator
            ->setController(PaymentCrudController::class)
            ->setAction('index')
            ->set('filters[created_at][comparison]', 'between')
            ->set('filters[created_at][value]', $dateFrom->format('Y-m-d\TH:i'))
            ->set('filters[created_at][value2]', $dateTo->format('Y-m-d\TH:i'))
            ->set('sort[amount_in_usd]', 'DESC')
        ;

        if ($tagId) {
            $url->set('filters[tag][comparison]', '=')
                ->set('filters[tag][value]', $tagId);
        }

        return $url->generateUrl();
    }

    private function getExpensesByTagByMonthChart(string $monthKey): Chart
    {
        $data = $this->cache->get(
            'expenses_by_tag_chart__' . str_replace(' ', '_', $monthKey),
            function (ItemInterface $item) use ($monthKey) {
                $item->tag(DashboardController::DASHBOARD_CACHE_TAG);

                $tags = $this->getExpensesByTagByMonth($monthKey);
                $totalExpenses = $this->getTotalExpensesByMonth($monthKey);

                $data = array_map(function ($tag) use ($totalExpenses) {
                    return [
                        'label' => $tag['name'],
                        'value' => number_format($tag['total'] / $totalExpenses * 100, 1, '.', ''),
                    ];
                }, $tags);

                usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

                return $data;
            }
        );

        return $this->getDoughnutChart($data, 'In %');
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
