<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function isUserDriver(User $user): bool
    {
        try {
            // Debug: Log user ID to check if we're getting the right user
            error_log('Checking if user ID ' . $user->getId_user() . ' is a driver');

            // Create a simple direct DQL query to ensure clarity
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'SELECT COUNT(id_driver) as driver_count FROM driver WHERE id_user = :userId';
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery(['userId' => $user->getId_user()]);
            $count = (int)$result->fetchOne();
            
            // Debug: Log the result for debugging
            error_log('Driver count for user ' . $user->getId_user() . ': ' . $count);
            
            return $count > 0;
        } catch (\Exception $e) {
            // Debug: Log any errors
            error_log('Error checking if user is driver: ' . $e->getMessage());
            return false;
        }
    }
}