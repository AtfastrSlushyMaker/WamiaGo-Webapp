<?php

namespace App\Controller;

use App\Service\TripService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Trip;

final class TripOwnerController extends AbstractController
{
    #[Route('/trip/owner', name: 'app_trip_owner')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Fetch trips for the static driver with ID 1
        $driverId = 1;
        $driverTrips = $entityManager->getRepository(Trip::class)->findBy(['driver' => $driverId]);

        // Get all cities from the Zone enum
        $cities = array_map(fn($zone) => $zone->value, \App\Enum\Zone::cases());

        // Pass the trips and cities to the template
        return $this->render('front/carpooling/DriverTripsManagement.twig', [
            'driverTrips' => $driverTrips,
            'cities' => $cities,
        ]);
    }

    /**
     * @throws \Exception
     */


#[Route('/driver/trip/save', name: 'app_driver_trip_save', methods: ['POST'])]
public function saveTrip(Request $request, TripService $tripService, EntityManagerInterface $entityManager): Response
{
    // Static values for driver and vehicle
    $driverId = 1;
    $vehicleId = 1;
    $cities = array_map(fn($zone) => $zone->value, \App\Enum\Zone::cases());

    // Retrieve form data
    $tripId = $request->request->get('tripId');
    $departureCity = $request->request->get('departureCity');
    $arrivalCity = $request->request->get('arrivalCity');
    $departureDate = $request->request->get('departureDate');
    $departureTime = $request->request->get('departureTime');
    $availableSeats = (int) $request->request->get('availableSeats');
    $pricePerPassenger = (float) $request->request->get('pricePerPassenger');

    // Retrieve the trip by ID
    $trip = $entityManager->getRepository(Trip::class)->find($tripId);

    if (!$trip) {
        $this->addFlash('error', 'Trip not found.');
        return $this->redirectToRoute('app_trip_owner');
    }

    // Prepare updated trip data
    $tripData = [
        'departure_city' => $departureCity,
        'arrival_city' => $arrivalCity,
        'departure_date' => $departureDate . ' ' . $departureTime,
        'available_seats' => $availableSeats,
        'price_per_passenger' => $pricePerPassenger,
        'id_driver' => $driverId,
        'id_vehicle' => $vehicleId,
    ];

    // Update the trip
    $tripService->updateTrip($trip, $tripData);

    $this->addFlash('success', 'Trip updated successfully.');

    // Fetch driver trips
    $driverTrips = $entityManager->getRepository(Trip::class)->findBy(['driver' => $driverId]);

    // Pass cities and driver trips to the template
    return $this->render('front/carpooling/DriverTripsManagement.twig', [
        'driverTrips' => $driverTrips,
        'cities' => $cities, // Pass cities to the template
    ]);
}
    #[Route('/driver/trip/delete', name: 'app_driver_trip_delete', methods: ['POST'])]
    public function deleteTrip(Request $request, TripService $tripService, EntityManagerInterface $entityManager): Response
    {
        $tripId = (int) $request->request->get('tripId');

        // Fetch the trip by ID
        $trip = $tripService->getTrip($tripId);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('app_trip_owner');
        }

        // Delete the trip
        $tripService->deleteTrip($trip);

        $this->addFlash('success', 'Trip deleted successfully.');

        return $this->redirectToRoute('app_trip_owner');
    }

}