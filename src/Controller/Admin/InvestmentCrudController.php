<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use App\Entity\Investment;
use App\Entity\Payment;
use App\Repository\ConfigurationRepository;
use App\Repository\InvestmentRepository;
use App\Utils\PriceUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InvestmentCrudController extends AbstractCrudController
{
    private const STOCKS_API_REALTIME_ENDPOINT = 'https://eodhd.com/api/real-time/';

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
                ->formatValue(fn($value) => PriceUtils::format($value)),
            NumberField::new('value')
                ->formatValue(fn($value) => PriceUtils::format($value))
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

    public function configureActions(Actions $actions): Actions
    {
        $updateRates = Action::new('updatePrices')
            ->linkToCrudAction('updatePrices')
            ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $updateRates);
    }

    /**
     * @see https://eodhd.com/financial-apis/live-ohlcv-stocks-api
     */
    public function updatePrices(
        EntityManagerInterface $entityManager,
        HttpClientInterface $client,
        AdminUrlGenerator $adminUrlGenerator,
    ): RedirectResponse {
        /** @var ConfigurationRepository $configRepository */
        $configRepository = $entityManager->getRepository(Configuration::class);

        $apiKey = $configRepository->getByName('stocks_api/key');

        if (!$apiKey) {
            throw new \LogicException('Stocks API Key is not set');
        }

        /** @var InvestmentRepository $investmentRepository */
        $investmentRepository = $entityManager->getRepository(Investment::class);

        $investments = $investmentRepository->findAll();

        if (!\count($investments)) {
            throw new \LogicException('No investments yet');
        }

        $mainInvestment = $investments[0];
        $restInvestments = array_slice($investments, 1);

        $url = self::STOCKS_API_REALTIME_ENDPOINT . $mainInvestment->getName()
            . '?api_token=' . $apiKey
            . '&fmt=json';

        if (count($restInvestments)) {
            $url .= '&s=' . join(',', array_map(fn(Investment $investment) => $investment->getName(), $restInvestments));
        }

        $response = $client->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            throw new \LogicException('Stocks API request error');
        }

        $content = $response->toArray();

        if (!array_is_list($content)) {
            $content = [$content];
        }

        foreach ($investments as $investment) {
            $data = array_find($content, fn($item) => $item['code'] === $investment->getName());
            $investment->setPrice($data['close']);

            $entityManager->persist($investment);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Prices are successfully updated');

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
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
