<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Driver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findByDriver(Driver $driver): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.announcement', 'a')
            ->where('a.driver = :driver')
            ->setParameter('driver', $driver)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}