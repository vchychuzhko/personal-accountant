<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConverterController extends AbstractDashboardController
{
    public function __construct(
        private readonly CurrencyRepository $currencyRepository,
    ) {
    }

    #[Route('/admin/converter', name: 'app_admin_converter')]
    public function index(): Response
    {
        $currencies = $this->currencyRepository->findAll();

        return $this->render('admin/converter.html.twig', [
            'currencies' => array_map(fn (Currency $currency) => [
                'name' => $currency->getName(),
                'code' => $currency->getCode(),
                'rate' => $currency->getRate(),
            ], $currencies),
        ]);
    }
}
