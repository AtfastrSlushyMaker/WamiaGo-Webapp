<?php

namespace App\Controller\Admin;

use App\Entity\BicycleRental;
use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/bicycle-rental-debug')]
class BicycleRentalDebugController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_bicycle_rental_debug')]
    public function debug(Request $request): Response
    {
        $output = [];
        
        // Test 1: Raw SQL count
        try {
            $conn = $this->entityManager->getConnection();
            $sql = "SHOW TABLES LIKE 'bicycle_rental'";
            $exists = $conn->executeQuery($sql)->fetchOne();
            $output['table_exists'] = !empty($exists);
            
            // If the table exists, get the structure
            if ($output['table_exists']) {
                // Count rows
                $sql = "SELECT COUNT(*) FROM bicycle_rental";
                $count = $conn->executeQuery($sql)->fetchOne();
                $output['sql_count'] = $count;
                
                // Get structure
                $sql = "DESCRIBE bicycle_rental";
                $structure = $conn->executeQuery($sql)->fetchAllAssociative();
                $output['table_structure'] = $structure;
                
                // Get sample rows
                if ($count > 0) {
                    $sql = "SELECT * FROM bicycle_rental LIMIT 3";
                    $sampleRows = $conn->executeQuery($sql)->fetchAllAssociative();
                    $output['sample_rows'] = $sampleRows;
                }
            }
        } catch (\Exception $e) {
            $output['sql_error'] = $e->getMessage();
        }
        
        // Test 2: ORM Repository findAll()
        try {
            $rentals = $this->entityManager->getRepository(BicycleRental::class)->findAll();
            $output['repository_count'] = count($rentals);
            
            if (count($rentals) > 0) {
                $output['first_rental_id'] = $rentals[0]->getId_user_rental();
            }
        } catch (\Exception $e) {
            $output['repository_error'] = $e->getMessage();
        }
        
        // Test 3: Query Builder
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('r')
               ->from(BicycleRental::class, 'r');
            
            $query = $qb->getQuery();
            $rentals = $query->getResult();
            
            $output['query_builder_count'] = count($rentals);
        } catch (\Exception $e) {
            $output['query_builder_error'] = $e->getMessage();
        }
        
        // Test 4: Check if we can create bicycle entities 
        try {
            $bicycle = new Bicycle();
            $station = new BicycleStation();
            $rental = new BicycleRental();
            
            $output['can_create_entities'] = true;
            $output['bicycle_class'] = get_class($bicycle);
            $output['rental_class'] = get_class($rental);
            
            // Check entity mappings
            $metadata = $this->entityManager->getClassMetadata(BicycleRental::class);
            $output['entity_table'] = $metadata->getTableName();
            $output['entity_fields'] = $metadata->getFieldNames();
            $output['entity_associations'] = array_keys($metadata->associationMappings);
        } catch (\Exception $e) {
            $output['entity_error'] = $e->getMessage();
        }
        
        // Test 5: Create & persist a rental if needed
        if (($output['sql_count'] ?? 0) == 0) {
            try {
                // Only attempt to create if there are no existing rentals
                $conn = $this->entityManager->getConnection();
                
                // Find a bicycle
                $bicycle = $this->entityManager->getRepository(Bicycle::class)->findOneBy([]);
                
                if ($bicycle) {
                    $rental = new BicycleRental();
                    $rental->setBicycle($bicycle);
                    $rental->setStartStation($bicycle->getBicycleStation());
                    $rental->setStart_time(new \DateTime());
                    $rental->setDistance_km(5.0);
                    $rental->setBattery_used(10.0);
                    $rental->setCost(15.0);
                    
                    // Persist and flush
                    $this->entityManager->persist($rental);
                    $this->entityManager->flush();
                    
                    $output['created_rental'] = true;
                    $output['new_rental_id'] = $rental->getId_user_rental();
                } else {
                    $output['created_rental'] = false;
                    $output['create_error'] = 'No bicycles found to create rental';
                }
            } catch (\Exception $e) {
                $output['create_error'] = $e->getMessage();
            }
        }
        
        return $this->render('back-office/bicycle/debug.html.twig', [
            'output' => $output
        ]);
    }
}