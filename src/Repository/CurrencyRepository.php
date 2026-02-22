<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Currency>
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    /**
     * @return Currency[] Returns an array of Currency objects
     */
    public function findNonUsd(Admin $admin): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code != :val')
            ->andWhere('c.admin = :admin')
            ->setParameter('val', 'USD')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult()
        ;
    }
}
