<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Driver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Enum\ReservationStatus;

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
    public function countByStatus(ReservationStatus $status): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id_reservation)')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function createSearchQuery(?string $keyword, ?string $status, ?string $date)
{
    $qb = $this->createQueryBuilder('r')
        ->leftJoin('r.announcement', 'a')
        ->leftJoin('r.user', 'u');

if ($keyword) {
        $qb->andWhere('a.title LIKE :keyword OR u.name LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%');
    }

    if ($status) {
        $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
    }

    if ($date) {
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj instanceof \DateTime) {
            // Clone pour éviter la modification de l'objet original
            $startDate = $dateObj->format('Y-m-d 00:00:00');
            $endDate = (clone $dateObj)->modify('+1 day')->format('Y-m-d 00:00:00');

            $qb->andWhere('r.date >= :start AND r.date < :end')
               ->setParameter('start', $startDate)
               ->setParameter('end', $endDate);
        }
    }

    
    
    return $qb->getQuery();
}

// Ajoutez cette méthode si elle n'existe pas
public function createSearchQueryBuilder(array $filters)
{
    $qb = $this->createQueryBuilder('r')
        ->leftJoin('r.announcement', 'a')
        ->leftJoin('r.user', 'u')
        ->addSelect('a', 'u');

    // Filtre date identique aux relocations
    if (!empty($filters['date'])) {
        $date = \DateTime::createFromFormat('Y-m-d', $filters['date']);
        if ($date instanceof \DateTime) {
            $start = $date->format('Y-m-d 00:00:00');
            $end = (clone $date)->modify('+1 day')->format('Y-m-d 00:00:00');
            
            $qb->andWhere('r.date >= :start AND r.date < :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }
    }

    // Filtre statut
    if (!empty($filters['status'])) {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', $filters['status']);
    }

    // Filtre mot-clé
    if (!empty($filters['keyword'])) {
        $qb->andWhere('a.title LIKE :keyword OR u.name LIKE :keyword')
           ->setParameter('keyword', '%'.$filters['keyword'].'%');
    }

    return $qb->getQuery();
}

public function findWithFilters(?string $keyword = null, ?string $status = null, ?string $date = null): QueryBuilder
{
    $qb = $this->createQueryBuilder('r')
        ->leftJoin('r.announcement', 'a')
        ->leftJoin('r.user', 'u')
        ->leftJoin('r.startLocation', 'sl')
        ->leftJoin('r.endLocation', 'el')
        ->orderBy('r.date', 'DESC');
    
    // Filtre mot-clé
    if ($keyword) {
        $qb->andWhere('
            a.title LIKE :keyword OR 
            u.name LIKE :keyword OR 
            sl.address LIKE :keyword OR 
            el.address LIKE :keyword
        ')
        ->setParameter('keyword', '%' . $keyword . '%');
    }
    
    // Filtre statut
    if ($status) {
        $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
    }
    
    // Filtre date corrigé
    if ($date) {
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj) {
            $start = $dateObj->format('Y-m-d 00:00:00');
            $end = (clone $dateObj)->modify('+1 day')->format('Y-m-d 00:00:00');
            
            $qb->andWhere('r.date >= :start AND r.date < :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }
    }
    
    return $qb;
}

public function findWithFilters_client(
    ?string $keyword = null,
    ?string $status = null,
    ?string $date = null
): QueryBuilder {
    $qb = $this->createQueryBuilder('r')
        ->leftJoin('r.announcement', 'a')
        ->leftJoin('r.user', 'u')
        ->leftJoin('r.startLocation', 'sl')
        ->leftJoin('r.endLocation', 'el')
        ->orderBy('r.date', 'DESC');

    // Filtre mot-clé
    if ($keyword) {
        $qb->andWhere('
            a.title LIKE :keyword OR 
            u.name LIKE :keyword OR 
            sl.address LIKE :keyword OR 
            el.address LIKE :keyword
        ')
        ->setParameter('keyword', '%' . $keyword . '%');
    }

    // Filtre statut
    if ($status) {
        $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
    }

    // Filtre date
    if ($date) {
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj instanceof \DateTime) {
            // Création des dates de début et fin
            $start = (clone $dateObj)->setTime(0, 0, 0);
            $end = (clone $dateObj)->modify('+1 day')->setTime(0, 0, 0);

            $qb->andWhere('r.date >= :start AND r.date < :end')
               ->setParameter('start', $start->format('Y-m-d H:i:s'))
               ->setParameter('end', $end->format('Y-m-d H:i:s'));
        }
    }

    return $qb;
}
    
}