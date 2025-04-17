<?php

namespace App\Repository;

use App\Entity\Relocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use App\Entity\Driver;


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
