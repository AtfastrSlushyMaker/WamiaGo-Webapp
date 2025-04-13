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
