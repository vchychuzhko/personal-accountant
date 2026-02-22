<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Configuration;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
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

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $qb->andWhere('entity.admin = :admin')
            ->setParameter('admin', $this->getUser());

        return $qb;
    }

    public function createEntity(string $entityFqcn): Configuration
    {
        $entity = new Configuration();
        /** @var Admin $admin */
        $admin = $this->getUser();
        $entity->setAdmin($admin);

        return $entity;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('label'),
            TextField::new('name')
                ->setDisabled(),
            TextField::new('value')
                ->setTemplatePath('admin/fields/secured_text.html.twig'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $clearCache = Action::new('clearCache')
            ->linkToCrudAction('clearCache')
            ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $clearCache)
            ->setPermission(Action::DELETE, 'ROLE_BLOCKED')
        ;
    }

    public function clearCache(
        TagAwareCacheInterface $cache,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse {
        /** @var Admin $admin */
        $admin = $this->getUser();
        $cache->invalidateTags([DashboardController::getCacheTag($admin)]);

        $this->addFlash('success', 'Cache is successfully cleared');

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
    }
}
