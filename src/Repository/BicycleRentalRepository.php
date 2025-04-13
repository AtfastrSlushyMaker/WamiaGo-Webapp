<?php

namespace App\Repository;

use App\Entity\BicycleRental;
use App\Enum\BICYCLE_STATUS;
use Doctrine\ORM\EntityRepository;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BicycleRental>
 */
class BicycleRentalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BicycleRental::class);
    }

    public function findActiveRentalsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')  // Use the proper association field
            ->andWhere('r.end_time IS NULL')
            ->setParameter('user', $user)  // Pass the user entity directly
            ->orderBy('r.start_time', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveRentals(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.bicycle', 'b')
            ->where('r.user = :user')
            ->andWhere('r.end_time IS NULL')
            ->andWhere('b.status != :in_use')
            ->orderBy('r.start_time', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('in_use', BICYCLE_STATUS::IN_USE)
            ->getQuery()
            ->getResult();
    }

    public function findPastRentalsByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->andWhere('r.end_time IS NOT NULL')
            ->setParameter('userId', $userId)
            ->orderBy('r.end_time', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllRentalsForAdmin(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'u', 'b', 'ss', 'es')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.bicycle', 'b')
            ->leftJoin('r.start_station', 'ss')
            ->leftJoin('r.end_station', 'es')
            ->orderBy('r.id_user_rental', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllRentals(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.id_user_rental', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'u', 'b', 'ss', 'es')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.bicycle', 'b')
            ->leftJoin('r.startStation', 'ss')
            ->leftJoin('r.endStation', 'es')
            ->orderBy('r.id_user_rental', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForAdmin(): array
    {
        try {
            // Main approach with full entity loading
            return $this->findAllWithRelations();
        } catch (\Exception $e) {
            // Fallback approach if the main one fails
            try {
                return $this->createQueryBuilder('r')
                    ->orderBy('r.id_user_rental', 'DESC')
                    ->getQuery()
                    ->getResult();
            } catch (\Exception $e2) {
                // Last resort - direct SQL
                $conn = $this->getEntityManager()->getConnection();
                $sql = "SELECT * FROM bicycle_rental ORDER BY id_user_rental DESC";
                $stmt = $conn->prepare($sql);
                $resultSet = $stmt->executeQuery();
                $rawData = $resultSet->fetchAllAssociative();
                
                // Convert raw data to objects
                $results = [];
                foreach ($rawData as $row) {
                    $rental = new BicycleRental();
                    // Map properties from raw data
                    $rental->setIdUserRental($row['id_user_rental']);
                    
                    // Handle dates safely
                    if (isset($row['start_time']) && $row['start_time']) {
                        $rental->setStartTime(new \DateTime($row['start_time']));
                    }
                    
                    if (isset($row['end_time']) && $row['end_time']) {
                        $rental->setEndTime(new \DateTime($row['end_time']));
                    }
                    
                    if (isset($row['cost'])) {
                        $rental->setCost($row['cost']);
                    }
                    
                    $results[] = $rental;
                }
                
                return $results;
            }
        }
    }

    public function findAllSafe(): array
{
    return $this->createQueryBuilder('r')
        ->select('r', 'u', 'b', 'ss', 'es')
        ->leftJoin('r.user', 'u')
        ->leftJoin('r.bicycle', 'b')
        ->leftJoin('r.startStation', 'ss')
        ->leftJoin('r.endStation', 'es')
        ->orderBy('r.id_user_rental', 'DESC')
        ->getQuery()
        ->getResult();
    }

    //    /**
    //     * @return BicycleRental[] Returns an array of BicycleRental objects
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

    //    public function findOneBySomeField($value): ?BicycleRental
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
