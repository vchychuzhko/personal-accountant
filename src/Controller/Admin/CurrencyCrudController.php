<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Configuration;
use App\Entity\Currency;
use App\Repository\ConfigurationRepository;
use App\Repository\CurrencyRepository;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
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

    public static function getEntityFqcn(): string
    {
        return Currency::class;
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

    public function createEntity(string $entityFqcn): Currency
    {
        $entity = new Currency();
        /** @var Admin $admin */
        $admin = $this->getUser();
        $entity->setAdmin($admin);

        return $entity;
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

            FormField::addFieldset()
                ->onlyOnDetail(),
            ArrayField::new('balances')
                ->setTemplatePath('admin/fields/balances_by_currency.html.twig')
                ->hideOnForm(),
            ArrayField::new('active_deposits')
                ->setTemplatePath('admin/fields/deposits_by_currency.html.twig')
                ->hideOnForm(),
            ArrayField::new('investments')
                ->setTemplatePath('admin/fields/investments_by_currency.html.twig')
                ->hideOnForm(),
            ArrayField::new('loans')
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
        /** @var Admin $admin */
        $admin = $this->getUser();

        /** @var ConfigurationRepository $configRepository */
        $configRepository = $entityManager->getRepository(Configuration::class);

        $apiKey = $configRepository->getByName('currency_api/key', $admin);

        if (!$apiKey) {
            throw new \LogicException('Currency API Key is not set');
        }

        /** @var CurrencyRepository $currencyRepository */
        $currencyRepository = $entityManager->getRepository(Currency::class);

        $currencies = $currencyRepository->findNonUsd($admin);

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

        $cache->invalidateTags([DashboardController::getCacheTag($admin)]);

        $this->addFlash('success', 'Rates are successfully updated');

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($targetUrl);
    }
}
