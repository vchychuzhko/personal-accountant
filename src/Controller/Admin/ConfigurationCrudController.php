<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
            FormField::addColumn(12),
            FormField::addFieldset(),
            TextField::new('label')
                ->setDisabled(),
            TextField::new('value')
                ->hideOnIndex()
                ->setTemplatePath('admin/fields/secured_text.html.twig'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['label' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $clearCache = Action::new('clearCache')
            ->linkToCrudAction('clearCache')
            ->renderAsForm()
            ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $clearCache)
            ->setPermission(Action::NEW, 'ROLE_BLOCKED')
            ->setPermission(Action::DELETE, 'ROLE_BLOCKED')
        ;
    }

    #[AdminRoute('/clear-cache', options: ['methods' => ['POST']])]
    public function clearCache(
        TagAwareCacheInterface $cache,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse {
        $cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $this->addFlash('success', 'Cache is successfully cleared');

        $targetUrl = $adminUrlGenerator
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
    }
}
