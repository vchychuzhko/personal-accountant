<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Tag;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Tag::class;
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

        $sortFields = $searchDto->getSort();

        if (isset($sortFields['payments_count'])) {
            $sortDirection = $sortFields['payments_count'];

            $qb->leftJoin('entity.payments', 'payment')
                ->addSelect('COUNT(payment.id) AS HIDDEN payments_count')
                ->groupBy('entity.id')
                ->orderBy('payments_count', $sortDirection);
        }

        return $qb;
    }

    public function createEntity(string $entityFqcn): Tag
    {
        $entity = new Tag();
        /** @var Admin $admin */
        $admin = $this->getUser();
        $entity->setAdmin($admin);

        return $entity;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            NumberField::new('payments_count', 'Payments')
                ->setTemplatePath('admin/fields/payments_by_tag.html.twig')
                ->setSortable(true)
                ->hideOnForm(),
        ];
    }
}
