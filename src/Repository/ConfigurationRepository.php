<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Configuration>
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    public function getByName(string $value, Admin $admin): mixed
    {
        $setting = $this->createQueryBuilder('c')
            ->andWhere('c.name = :val')
            ->andWhere('c.admin = :admin')
            ->setParameter('val', $value)
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $setting ? $setting->getValue() : null;
    }
}
