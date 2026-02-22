<?php

namespace App\Repository;

use App\Entity\Admin;
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
    public function findAllActive(Admin $admin): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.balance', 'b')
            ->andWhere('d.status = :val')
            ->andWhere('b.admin = :admin')
            ->setParameter('val', Deposit::STATUS_ACTIVE)
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult()
        ;
    }
}
