<?php

namespace App\Repository;

use App\Entity\Admin;
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
    public function findByAdmin(Admin $admin): array
    {
        return $this->findBy(['admin' => $admin]);
    }
}
