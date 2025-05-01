<?php

// This script checks the bicycle_rental table and adds test data if needed
require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\BicycleRental;
use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\User;
use App\Enum\BICYCLE_STATUS;

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Check if there are any rentals
$rentalRepo = $entityManager->getRepository(BicycleRental::class);
$rentals = $rentalRepo->findAll();

echo "Current bicycle rental count: " . count($rentals) . "\n";

// If there are no rentals, create some test data
if (count($rentals) === 0) {
    echo "No rentals found. Creating test rental data...\n";
    
    // Find a bicycle to use
    $bicycle = $entityManager->getRepository(Bicycle::class)->findOneBy([]);
    
    if (!$bicycle) {
        echo "No bicycles found. Creating a test bicycle...\n";
        
        // Find a station
        $station = $entityManager->getRepository(BicycleStation::class)->findOneBy([]);
        
        if (!$station) {
            echo "No stations found. Please create a station first.\n";
            exit(1);
        }
        
        // Create a bicycle
        $bicycle = new Bicycle();
        $bicycle->setBicycleStation($station);
        $bicycle->setBatteryLevel(95.0);
        $bicycle->setRangeKm(35.0);
        $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
        $bicycle->setLastUpdated(new \DateTime());
        
        $entityManager->persist($bicycle);
        $entityManager->flush();
        
        echo "Created test bicycle with ID: " . $bicycle->getIdBike() . "\n";
    }
    
    // Find a user
    $user = $entityManager->getRepository(User::class)->findOneBy([]);
    
    if (!$user) {
        echo "No users found. Please create a user first.\n";
        exit(1);
    }
    
    // Find stations
    $stations = $entityManager->getRepository(BicycleStation::class)->findAll();
    
    if (count($stations) < 2) {
        echo "Not enough stations found. You need at least 2 stations.\n";
        exit(1);
    }
    
    // Create a completed rental
    $completedRental = new BicycleRental();
    $completedRental->setUser($user);
    $completedRental->setBicycle($bicycle);
    $completedRental->setStartStation($stations[0]);
    $completedRental->setEndStation($stations[1]);
    $completedRental->setStartTime(new \DateTime('-2 hours'));
    $completedRental->setEndTime(new \DateTime('-1 hour'));
    $completedRental->setDistanceKm(5.5);
    $completedRental->setBatteryUsed(10.0);
    $completedRental->setCost(12.50);
    
    $entityManager->persist($completedRental);
    
    // Create an active rental
    $activeRental = new BicycleRental();
    $activeRental->setUser($user);
    $activeRental->setBicycle($bicycle);
    $activeRental->setStartStation($stations[0]);
    $activeRental->setStartTime(new \DateTime('-30 minutes'));
    $activeRental->setDistanceKm(0.0);
    $activeRental->setBatteryUsed(5.0);
    $activeRental->setCost(5.00);
    
    $entityManager->persist($activeRental);
    
    // Create a reserved rental
    $reservedRental = new BicycleRental();
    $reservedRental->setUser($user);
    $reservedRental->setBicycle($bicycle);
    $reservedRental->setStartStation($stations[0]);
    $reservedRental->setDistanceKm(0.0);
    $reservedRental->setBatteryUsed(0.0);
    $reservedRental->setCost(0.00);
    
    $entityManager->persist($reservedRental);
    
    // Save all
    $entityManager->flush();
    
    echo "Created 3 test rentals (completed, active, and reserved).\n";
    echo "Rental IDs: " . $completedRental->getIdUserRental() . ", " . 
         $activeRental->getIdUserRental() . ", " . 
         $reservedRental->getIdUserRental() . "\n";
}

echo "Done.\n";