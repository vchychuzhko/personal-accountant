<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ConfigurationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Configuration::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('label'),
            TextField::new('name')
                ->setDisabled(),
            TextField::new('value'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $clearCache = Action::new('clearCache')
            ->linkToCrudAction('clearCache')
            ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $clearCache);
    }

    public function clearCache(
        TagAwareCacheInterface $cache,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse {
        $cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $this->addFlash('success', '"' . DashboardController::DASHBOARD_CACHE_TAG . '" cache is successfully cleared');

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
    }
}
