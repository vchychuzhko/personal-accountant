<?php

namespace App\Repository;

use App\Entity\Deposit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deposit>
 */
class DepositRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    /**
     * @return Deposit[] Returns an array of Deposit objects
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :val')
            ->setParameter('val', Deposit::STATUS_ACTIVE)
            ->getQuery()
            ->getResult()
        ;
    }
}
