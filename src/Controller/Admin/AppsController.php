<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppsController extends AbstractController
{
    public function __construct(
        private readonly CurrencyRepository $currencyRepository,
    ) {
    }

    #[Route(path: '/admin/apps', name: 'app_admin_apps')]
    public function index(): Response
    {
        $currencies = $this->currencyRepository->findAll();

        return $this->render('admin/apps.html.twig', [
            'currencies' => array_map(fn (Currency $currency) => [
                'name' => $currency->getName(),
                'code' => $currency->getCode(),
                'rate' => $currency->getRate(),
            ], $currencies),
        ]);
    }
}
