<?php

namespace App\Repository;

use App\Entity\BicycleStation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Enum\BICYCLE_STATUS;

/**
 * @extends ServiceEntityRepository<BicycleStation>
 */
class BicycleStationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BicycleStation::class);
    }
    public function getStationsWithAvailableBikes(): array
    {
        // Return stations that have at least one bike available
        return $this->createQueryBuilder('s')
            ->where('s.available_bikes > 0')
            ->andWhere('s.status = :active')
            ->setParameter('active', BICYCLE_STATUS::AVAILABLE)
            ->getQuery()
            ->getResult();
    }

    /*
     * Get count of available bikes for stations
     */
    public function updateAvailableBikesCounts(): void
    {
        $conn = $this->getEntityManager()->getConnection();

        // Update all station available_bikes counts based on actual bicycles
        $sql = '
        UPDATE bicycle_station s
        SET s.available_bikes = (
            SELECT COUNT(b.id_bike)
            FROM bicycle b
            WHERE b.id_station = s.id_station
            AND b.status = :available
        )
    ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('available', BICYCLE_STATUS::AVAILABLE->value);
        $stmt->executeStatement();
    }

    //    /**
    //     * @return BicycleStation[] Returns an array of BicycleStation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BicycleStation
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
