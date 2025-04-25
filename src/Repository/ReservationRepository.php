<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Driver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

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

    public function save(Reservation $reservation): void
    {
        $this->getEntityManager()->persist($reservation);
        $this->getEntityManager()->flush();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getQueryByDriver(Driver $driver): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.announcement', 'a')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.startLocation', 'sl')
            ->leftJoin('r.endLocation', 'el')
            ->addSelect('a', 'u', 'sl', 'el')
            ->where('a.driver = :driver')
            ->setParameter('driver', $driver)
            ->orderBy('r.date', 'DESC');
    }

    public function findWithDetails(int $id): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.announcement', 'a')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.startLocation', 'sl')
            ->leftJoin('r.endLocation', 'el')
            ->addSelect('a', 'u', 'sl', 'el')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}