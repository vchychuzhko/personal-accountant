<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Entity\Currency;
use App\Entity\Tag;
use App\Repository\BalanceRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly BalanceRepository $balanceRepository,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $balances = $this->balanceRepository->findAll();
        $total = 0;

        foreach ($balances as $balance) {
            $currency = $balance->getCurrency();

            $total += $balance->getAmount() / $currency->getRate();
        }

        return $this->render('admin/index.html.twig', [
            'total' => number_format($total, 2, '.', ''),
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
        yield MenuItem::linkToCrud('Tag', 'fas fa-tag', Tag::class);
        yield MenuItem::linkToCrud('Currency', 'fas fa-dollar', Currency::class);
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->showEntityActionsInlined();
    }
}
