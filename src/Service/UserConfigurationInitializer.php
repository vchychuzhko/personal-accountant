<?php

namespace App\Service;

use App\Entity\Admin;
use App\Entity\Configuration;
use Doctrine\ORM\EntityManagerInterface;

class UserConfigurationInitializer
{
    private const DEFAULT_CONFIGS = [
        ['name' => 'timezone', 'label' => 'Timezone', 'value' => 'UTC'],
        ['name' => 'currency_api/key', 'label' => 'Currency API Key', 'value' => ''],
        ['name' => 'stocks_api/key', 'label' => 'Stocks API Key', 'value' => ''],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function initialize(Admin $admin): void
    {
        foreach (self::DEFAULT_CONFIGS as $configData) {
            $config = new Configuration();
            $config->setAdmin($admin);
            $config->setName($configData['name']);
            $config->setLabel($configData['label']);
            $config->setValue($configData['value']);

            $this->entityManager->persist($config);
        }

        $this->entityManager->flush();
    }
}
