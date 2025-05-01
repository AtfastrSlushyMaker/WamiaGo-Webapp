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


    public function getStationsWithRentalActivity(int $limit = 5): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT 
            s.id_station, 
            s.name, 
            COUNT(r.id_rental) as rental_count
        FROM 
            bicycle_station s
        LEFT JOIN 
            bicycle_rental r 
        ON 
            s.id_station = r.id_start_station
        GROUP BY 
            s.id_station, s.name
        ORDER BY 
            rental_count DESC
        LIMIT :limit
    ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result;
    }
}
