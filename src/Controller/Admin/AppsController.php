<?php

namespace App\Controller\Admin;

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

    #[Route(path: '/admin/apps', name: 'admin_apps')]
    public function index(): Response
    {
        return $this->render('admin/apps.html.twig', [
            'currencies' => $this->currencyRepository->findAll(),
        ]);
    }
}
