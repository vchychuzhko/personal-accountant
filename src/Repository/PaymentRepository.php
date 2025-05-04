<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * @return Payment[] Returns an array of Payment objects
     */
    public function findAfterDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.created_at > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Payment[] Returns an array of Payment objects
     */
    public function findBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.created_at > :from')
            ->andWhere('p.created_at < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
        ;
    }
}
