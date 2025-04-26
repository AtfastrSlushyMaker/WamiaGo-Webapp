<?php

namespace App\Repository;

use App\Entity\Announcement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Driver;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    public function save(Announcement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Announcement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Méthodes spécifiques inspirées de votre JavaFX
    public function findActiveAnnouncements(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', true)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByZone(string $zone): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.zone = :zone')
            ->andWhere('a.status = :status')
            ->setParameter('zone', $zone)
            ->setParameter('status', true)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDriverId(int $driverId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.driver', 'd')
            ->andWhere('d.id_driver = :driverId')
            ->setParameter('driverId', $driverId)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.title LIKE :keyword OR a.content LIKE :keyword')
            ->andWhere('a.status = :status')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->setParameter('status', true)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', true);

            if (isset($filters['keyword'])) {

            $qb->andWhere('a.title LIKE :keyword OR a.content LIKE :keyword')
               ->setParameter('keyword', '%'.$filters['keyword'].'%');
        }

        if (isset($filters['zone'])) {
            $qb->andWhere('a.zone = :zone')
               ->setParameter('zone', $filters['zone']);
        }

        if (isset($filters['date'])) {
            $qb->andWhere('DATE(a.date) = :date')
               ->setParameter('date', $filters['date']);
        }

        return $qb->orderBy('a.date', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    public function getQueryByDriver(Driver $driver): QueryBuilder
{
    return $this->createQueryBuilder('a')
        ->andWhere('a.driver = :driver')
        ->setParameter('driver', $driver)
        ->orderBy('a.date', 'DESC');
}

public function findWithDetails(int $id): ?Announcement
{
    return $this->createQueryBuilder('a')
        ->leftJoin('a.driver', 'd')
        ->addSelect('d')
        ->andWhere('a.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}

    public function createFilteredQuery(?string $keyword = null, ?string $zone = null, ?string $date = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.driver', 'd')
            ->addSelect('d');

        if ($keyword) {
            $qb->andWhere('LOWER(a.title) LIKE LOWER(:keyword) OR LOWER(a.content) LIKE LOWER(:keyword)')
               ->setParameter('keyword', '%' . $keyword . '%');
        }

        if ($zone) {
            $qb->andWhere('a.zone = :zone')
               ->setParameter('zone', $zone);
        }

        if ($date) {
            $qb->andWhere('DATE(a.date) = :date')
               ->setParameter('date', new \DateTime($date));
        }

        $qb->orderBy('a.date', 'DESC');

        return $qb;
    }
}