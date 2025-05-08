<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

<<<<<<< HEAD
    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
=======
    /**
     * Find users with expired reset tokens
     * 
     * This method is kept for backward compatibility but returns an empty array
     * since we're using a stateless approach without storing tokens in the database.
     * 
     * @param \DateTime $now Current datetime
     * @return User[] Array of users with expired tokens (always empty)
     */
    public function findExpiredResetTokens(\DateTime $now): array
    {
        // Return an empty array since we're using a stateless approach
        // with no tokens stored in the database
        return [];
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
    
    /**
     * Find users by search term with additional criteria and pagination
     */
    public function findBySearchTerm(string $search, array $criteria = [], string $sortBy = 'name', string $sortDir = 'asc', int $limit = 10, int $offset = 0)
    {
        try {
            $qb = $this->createQueryBuilder('u');
            
            if (!empty($search)) {
                $search = trim($search);
                $qb->andWhere('LOWER(u.name) LIKE LOWER(:search) OR LOWER(u.email) LIKE LOWER(:search) OR LOWER(u.phone_number) LIKE LOWER(:search)')
                   ->setParameter('search', '%' . mb_strtolower($search, 'UTF-8') . '%');
            }
            
            $this->addCriteriaToQueryBuilder($qb, $criteria);
            
            if (property_exists(User::class, $sortBy)) {
                $qb->orderBy('u.' . $sortBy, strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC');
            } else {
                $qb->orderBy('u.id_user', 'ASC');
            }
            
            // Debug log to track pagination parameters
            error_log("Pagination params - limit: {$limit}, offset: {$offset}, total criteria: " . count($criteria));
            
            // Add pagination
            if ($limit > 0) {
                $qb->setMaxResults($limit)
                   ->setFirstResult($offset);
            }
            return $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            // Log any errors that occur
            error_log('Error in findBySearchTerm: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count users by search term with additional criteria
     */
    public function countBySearchTerm(string $search, array $criteria = [])
    {
        try {
            $qb = $this->createQueryBuilder('u')
                    ->select('COUNT(u.id_user)');
            
            // Apply search filter if provided (case-insensitive)
            if (!empty($search)) {
                $search = trim($search);
                $qb->andWhere('LOWER(u.name) LIKE LOWER(:search) OR LOWER(u.email) LIKE LOWER(:search) OR LOWER(u.phone_number) LIKE LOWER(:search)')
                ->setParameter('search', '%' . mb_strtolower($search, 'UTF-8') . '%');
            }
            
            // Add additional criteria
            $this->addCriteriaToQueryBuilder($qb, $criteria);
            
            $count = $qb->getQuery()->getSingleScalarResult();
            
            // Debug log the count for troubleshooting
            error_log("Count result for search '{$search}': {$count}");
            
            return (int)$count;
        } catch (\Exception $e) {
            error_log('Error in countBySearchTerm: ' . $e->getMessage());
            // Return 0 as a fallback to prevent breaking the frontend
            return 0;
        }
    }
    
    /**
     * Check if a field exists in the User entity
     */
    private function hasField($field)
    {
        try {
            $metadata = $this->getEntityManager()->getClassMetadata(User::class);
            return $metadata->hasField($field) || $metadata->hasAssociation($field);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Add criteria to a query builder instance
     */
    private function addCriteriaToQueryBuilder($qb, array $criteria)
    {
        try {
            foreach ($criteria as $field => $value) {
                $paramName = 'param_' . $field;
                
                // Handle boolean values specially
                if (is_bool($value) || in_array($field, ['isVerified'])) {
                    $qb->andWhere('u.' . $field . ' = :' . $paramName)
                       ->setParameter($paramName, $value);
                } else {
                    $qb->andWhere('u.' . $field . ' = :' . $paramName)
                       ->setParameter($paramName, $value);
                }
                
                // Debug log each criteria being added
                error_log("Added criteria: {$field} = " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
            }
            
            return $qb;
        } catch (\Exception $e) {
            error_log('Error adding criteria: ' . $e->getMessage());
            return $qb;
        }
    }
}
>>>>>>> main
