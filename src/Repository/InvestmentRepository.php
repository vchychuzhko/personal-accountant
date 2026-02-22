<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\Investment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Investment>
 */
class InvestmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Investment::class);
    }

    /**
     * @return Investment[] Returns an array of Investment objects
     */
    public function findByAdmin(Admin $admin): array
    {
        return $this->findBy(['admin' => $admin]);
    }
}
