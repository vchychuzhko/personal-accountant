<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use App\Entity\Currency;
use App\Repository\ConfigurationRepository;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyCrudController extends AbstractCrudController
{
    private const CURRENCY_API_LATEST_ENDPOINT = 'https://api.currencyapi.com/v3/latest';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $client,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Currency::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            FormField::addFieldset(),
            TextField::new('name'),
            TextField::new('code'),
            NumberField::new('rate')
                ->setNumDecimals(2),
            TextField::new('format')
                ->setHelp('Use "%1" as placeholder for number value, e.g. "$%1"')
                ->hideOnIndex(),

            FormField::addFieldset('Balances')
                ->addCssClass('form-fieldset--no-labels')
                ->hideOnForm(),
            AssociationField::new('balances')
                ->setTemplatePath('admin/fields/balances_by_currency.html.twig')
                ->autocomplete()
                ->hideOnForm(),

            FormField::addFieldset('Active Deposits')
                ->addCssClass('form-fieldset--no-labels')
                ->hideOnForm(),
            ArrayField::new('active_deposits')
                ->setTemplatePath('admin/fields/deposits_by_currency.html.twig')
                ->hideOnForm(),

            FormField::addFieldset('Active Investments')
                ->addCssClass('form-fieldset--no-labels')
                ->onlyOnDetail(),
            ArrayField::new('active_investments')
                ->setTemplatePath('admin/fields/investments_by_currency.html.twig')
                ->onlyOnDetail(),

            FormField::addFieldset('Loans')
                ->addCssClass('form-fieldset--no-labels')
                ->onlyOnDetail(),
            AssociationField::new('loans')
                ->setTemplatePath('admin/fields/loans_by_currency.html.twig')
                ->onlyOnDetail(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['name' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $updateRates = Action::new('updateRates')
            ->linkToCrudAction('updateRates')
            ->setTemplatePath('admin/demo/update_rates_action.html.twig')
            ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $updateRates)
        ;
    }

    /**
     * @see https://currencyapi.com/docs/latest
     */
    #[AdminRoute('/update-rates')]
    public function updateRates(AdminContext $context): RedirectResponse
    {
        try {
            /** @var ConfigurationRepository $configRepository */
            $configRepository = $this->entityManager->getRepository(Configuration::class);

            $apiKey = $configRepository->getByName('currency_api/key');

            if (!$apiKey) {
                throw new \LogicException('Currency API Key is not set');
            }

            /** @var CurrencyRepository $currencyRepository */
            $currencyRepository = $this->entityManager->getRepository(Currency::class);

            $currencies = $currencyRepository->findNonUsd();

            $url = self::CURRENCY_API_LATEST_ENDPOINT
                . '?currencies=' . join(',', array_map(fn(Currency $currency) => $currency->getCode(), $currencies))
                . '&apikey=' . $apiKey;

            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                throw new \LogicException('Currency API request error');
            }

            $content = $response->toArray();

            foreach ($currencies as $currency) {
                $currency->setRate($content['data'][$currency->getCode()]['value']);

                $this->entityManager->persist($currency);
            }

            $this->entityManager->flush();

            $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

            $this->addFlash('success', 'Rates are successfully updated');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $targetUrl = $this->adminUrlGenerator
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
    }
}
