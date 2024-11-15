<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalculatorController extends AbstractDashboardController
{
    #[Route('/admin/calculator', name: 'app_admin_calculator')]
    public function index(): Response
    {
        return $this->render('admin/calculator.html.twig');
    }
}
