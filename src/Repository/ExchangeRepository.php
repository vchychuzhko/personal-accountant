<?php

namespace App\Repository;

use App\Entity\Exchange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exchange>
 */
class ExchangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exchange::class);
    }

    /**
     * @return Exchange[] Returns an array of Exchange objects
     */
    public function findAfterDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.created_at > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
        ;
    }
}
