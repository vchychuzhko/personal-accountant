<?php

namespace App\Repository;

use App\Entity\Admin;
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
    public function findAfterDate(\DateTimeInterface $date, Admin $admin): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.balance', 'b')
            ->where('p.created_at > :date')
            ->andWhere('b.admin = :admin')
            ->setParameter('date', $date)
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Payment[] Returns an array of Payment objects
     */
    public function findBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to, Admin $admin): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.balance', 'b')
            ->where('p.created_at > :from')
            ->andWhere('p.created_at < :to')
            ->andWhere('b.admin = :admin')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult()
        ;
    }
}
