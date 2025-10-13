<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use App\Entity\Currency;
use App\Repository\ConfigurationRepository;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyCrudController extends AbstractCrudController
{
    private const CURRENCY_API_LATEST_ENDPOINT = 'https://api.currencyapi.com/v3/latest';

    public static function getEntityFqcn(): string
    {
        return Currency::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            FormField::addFieldset(),
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('name'),
            TextField::new('code'),
            NumberField::new('rate')
                ->setNumDecimals(2),
            TextField::new('format')
                ->setHelp('Use "%1" as placeholder for number value, e.g. "$%1"')
                ->hideOnIndex(),

            FormField::addFieldset()
                ->onlyOnDetail(),
            CollectionField::new('balances')
                ->setTemplatePath('admin/fields/balances_by_currency.html.twig')
                ->hideOnForm(),
            CollectionField::new('active_deposits')
                ->setTemplatePath('admin/fields/deposits_by_currency.html.twig')
                ->hideOnForm(),
            CollectionField::new('investments')
                ->setTemplatePath('admin/fields/investments_by_currency.html.twig')
                ->hideOnForm(),
            CollectionField::new('loans')
                ->setTemplatePath('admin/fields/loans_by_currency.html.twig')
                ->hideOnForm(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $updateRates = Action::new('updateRates')
            ->linkToCrudAction('updateRates')
            ->createAsGlobalAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $updateRates);
    }

    /**
     * @see https://currencyapi.com/docs/latest
     */
    public function updateRates(
        EntityManagerInterface $entityManager,
        HttpClientInterface $client,
        AdminUrlGenerator $adminUrlGenerator,
        TagAwareCacheInterface $cache
    ): RedirectResponse {
        /** @var ConfigurationRepository $configRepository */
        $configRepository = $entityManager->getRepository(Configuration::class);

        $apiKey = $configRepository->getByName('currency_api/key');

        if (!$apiKey) {
            throw new \LogicException('Currency API Key is not set');
        }

        /** @var CurrencyRepository $currencyRepository */
        $currencyRepository = $entityManager->getRepository(Currency::class);

        $currencies = $currencyRepository->findNonUsd();

        $url = self::CURRENCY_API_LATEST_ENDPOINT
            . '?currencies=' . join(',', array_map(fn(Currency $currency) => $currency->getCode(), $currencies))
            . '&apikey=' . $apiKey;

        $response = $client->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            throw new \LogicException('Currency API request error');
        }

        $content = $response->toArray();

        foreach ($currencies as $currency) {
            $currency->setRate($content['data'][$currency->getCode()]['value']);

            $entityManager->persist($currency);
        }

        $entityManager->flush();

        $cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $this->addFlash('success', 'Rates are successfully updated');

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
    }
}
