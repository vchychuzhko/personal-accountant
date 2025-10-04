<?php

namespace App\Controller\Admin;

use App\Entity\Investment;
use App\Entity\Payment;
use App\Utils\PriceUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class InvestmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Investment::class;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $sortFields = $searchDto->getSort();

        if (isset($sortFields['value'])) {
            $sortDirection = $sortFields['value'];

            $qb->addSelect('(entity.share / entity.price) AS HIDDEN value')
                ->orderBy('value', $sortDirection);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            FormField::addFieldset(),
            TextField::new('name'),
            NumberField::new('share')
                ->setNumDecimals(4),
            NumberField::new('price')
                ->formatValue(fn ($value) => PriceUtils::format($value)),
            NumberField::new('value')
                ->formatValue(fn ($value) => PriceUtils::format($value))
                ->setSortable(true)
                ->hideOnForm(),

            FormField::addFieldset('Payments')
                ->addCssClass('form-fieldset--no-labels'),
            AssociationField::new('payments')
                ->setTemplatePath('admin/fields/payments_by_investment.html.twig')
                ->autocomplete()
                ->hideOnIndex(),
        ];
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Investment $entityInstance
     * @return void
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->syncPayments($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Investment $entityInstance
     * @return void
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->syncPayments($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function syncPayments(EntityManagerInterface $entityManager, Investment $entityInstance): void
    {
        $currentPayments = new ArrayCollection($entityInstance->getPayments()->toArray());

        $existingPayments = $entityManager->getRepository(Payment::class)->findBy(['investment' => $entityInstance]);

        // Detach removed
        foreach ($existingPayments as $existing) {
            if (!$currentPayments->contains($existing)) {
                $existing->setInvestment(null);
                $entityManager->persist($existing);
            }
        }

        // Attach added
        foreach ($currentPayments as $payment) {
            $payment->setInvestment($entityInstance);
            $entityManager->persist($payment);
        }
    }
}
