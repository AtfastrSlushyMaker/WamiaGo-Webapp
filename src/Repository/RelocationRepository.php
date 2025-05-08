<?php

namespace App\Repository;

use App\Entity\Relocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use App\Entity\Driver;
use App\Entity\User;


/**
 * @extends ServiceEntityRepository<Relocation>
 *
 * @method Relocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Relocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Relocation[]    findAll()
 * @method Relocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RelocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Relocation::class);
    }

    public function save(Relocation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Relocation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Crée une requête de base pour les relocations
     */
    public function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.reservation', 'res')
            ->leftJoin('res.announcement', 'a')
            ->leftJoin('res.user', 'u')
            ->addSelect('res', 'a', 'u');
    }

    /**
     * Trouve une relocation avec toutes ses relations chargées
     */
    public function findWithRelations(int $id): ?Relocation
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('r.id_relocation = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les relocations avec leurs relations
     * 
     * @return Relocation[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createBaseQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre les relocations par statut
     */
    public function findByStatus(bool $status): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre les relocations par date (après une date donnée)
     */
    public function findAfterDate(\DateTimeInterface $date): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('r.date >= :date')
            ->setParameter('date', $date)
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les relocations par ID de réservation
     */
    public function findByReservationId(int $reservationId): array
    {
        return $this->createBaseQueryBuilder()
            ->andWhere('res.id_reservation = :resId')
            ->setParameter('resId', $reservationId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de relocations
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id_relocation)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère le coût total des relocations
     */
    public function getTotalCost(): float
    {
        return $this->createQueryBuilder('r')
            ->select('SUM(r.cost)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function findByDriver(Driver $driver): array
{
    return $this->createQueryBuilder('r')
        ->join('r.reservation', 'res')
        ->join('res.announcement', 'a')
        ->where('a.driver = :driver')
        ->setParameter('driver', $driver)
        ->orderBy('r.date', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findByClient(User $client): array
{
    return $this->createBaseQueryBuilder() // Using your base query builder to load all relations
        ->andWhere('res.user = :client')
        ->setParameter('client', $client)
        ->orderBy('r.date', 'DESC')
        ->getQuery()
        ->getResult();
}
public function getQueryBuilderByClient(User $client): QueryBuilder
{
    return $this->createBaseQueryBuilder()
        ->andWhere('res.user = :client')
        ->setParameter('client', $client)
        ->orderBy('r.date', 'DESC');
}
public function createClientSearchQueryBuilder(User $client, array $filters): QueryBuilder
{
    $qb = $this->createBaseQueryBuilder()
        ->andWhere('res.user = :client')
        ->setParameter('client', $client);

    if (!empty($filters['keyword'])) {
        $qb->andWhere('a.title LIKE :keyword OR res.description LIKE :keyword')
           ->setParameter('keyword', '%' . $filters['keyword'] . '%');
    }

    if (isset($filters['status']) && $filters['status'] !== '') {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', (bool)$filters['status']);
    }

    if (!empty($filters['date'])) {
        $date = \DateTime::createFromFormat('Y-m-d', $filters['date']);
        $qb->andWhere('r.date BETWEEN :start AND :end')
           ->setParameter('start', $date->format('Y-m-d 00:00:00'))
           ->setParameter('end', $date->format('Y-m-d 23:59:59'));
    }

    return $qb->orderBy('r.date', 'DESC');
}






public function getQueryByDriver(Driver $driver): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->join('r.reservation', 'res')
            ->join('res.announcement', 'a')
            ->join('a.driver', 'd')
            ->where('d.id_driver = :driverId')
            ->setParameter('driverId', $driver->getIdDriver())
            ->orderBy('r.date', 'DESC');
    }

    public function createSearchQueryBuilder(array $filters): QueryBuilder
{
    $qb = $this->createQueryBuilder('r')
        ->leftJoin('r.reservation', 'res')
        ->leftJoin('res.announcement', 'a')
        ->addSelect('res', 'a');

    if (!empty($filters['keyword'])) {
        $qb->andWhere('a.title LIKE :keyword OR res.description LIKE :keyword')
           ->setParameter('keyword', '%' . $filters['keyword'] . '%');
    }

    if (isset($filters['status']) && $filters['status'] !== '') {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', (bool)$filters['status']);
    }

    if (!empty($filters['date'])) {
        $date = \DateTime::createFromFormat('Y-m-d', $filters['date']);
        $qb->andWhere('r.date BETWEEN :start AND :end')
           ->setParameter('start', $date->format('Y-m-d 00:00:00'))
           ->setParameter('end', $date->format('Y-m-d 23:59:59'));
    }

    return $qb->orderBy('r.date', 'DESC');
}

public function createSearchQueryBuilder_client(array $filters): QueryBuilder
{
    $qb = $this->createQueryBuilder('r')
        ->leftJoin('r.reservation', 'res')
        ->leftJoin('res.announcement', 'a')
        ->addSelect('res', 'a');

    if (!empty($filters['keyword'])) {
        $qb->andWhere('a.title LIKE :keyword OR res.description LIKE :keyword')
           ->setParameter('keyword', '%' . $filters['keyword'] . '%');
    }

    if (isset($filters['status']) && $filters['status'] !== '') {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', (bool)$filters['status']);
    }

    if (!empty($filters['date'])) {
        $date = \DateTime::createFromFormat('Y-m-d', $filters['date']);
        $qb->andWhere('r.date BETWEEN :start AND :end')
           ->setParameter('start', $date->format('Y-m-d 00:00:00'))
           ->setParameter('end', $date->format('Y-m-d 23:59:59'));
    }

    return $qb->orderBy('r.date', 'DESC');
}

public function createSearchQueryBuilder_admin(?string $keyword = null, ?string $zone = null, ?string $date = null): QueryBuilder
{
    $qb = $this->createQueryBuilder('a')
        ->orderBy('a.date', 'DESC');

    if ($keyword && trim($keyword) !== '') {
        $qb->andWhere('a.title LIKE :keyword OR a.content LIKE :keyword')
           ->setParameter('keyword', '%' . trim($keyword) . '%');
    }
    
    if ($zone && trim($zone) !== '') {
        $qb->andWhere('a.zone = :zone')
           ->setParameter('zone', $zone);
    }
    
    if ($date && trim($date) !== '') {
        $startDate = \DateTime::createFromFormat('Y-m-d', $date);
        $startDate->setTime(0, 0, 0);
        $endDate = clone $startDate;
        $endDate->modify('+1 day');
        
        $qb->andWhere('a.date >= :startDate AND a.date < :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);
    }
    
    return $qb;
}
    //    /**
    //     * @return Relocation[] Returns an array of Relocation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Relocation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
