<?php

namespace App\Repository;

use App\Entity\Income;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Income>
 */
class IncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    /**
     * @return Income[] Returns an array of Income objects
     */
    public function findAfterDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.created_at > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
        ;
    }
}
