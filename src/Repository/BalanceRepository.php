<?php

namespace App\Repository;

use App\Entity\Balance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Balance>
 */
class BalanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Balance::class);
    }

    /**
     * @return Balance[] Returns an array of Balance objects
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status = :status')
            ->setParameter('status', Balance::STATUS_ACTIVE)
            ->getQuery()
            ->getResult()
        ;
    }
}
