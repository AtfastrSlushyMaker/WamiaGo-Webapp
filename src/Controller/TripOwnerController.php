<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Service\PredictPrice;
use App\Service\TrafficTimeEstimator;
use App\Service\TripService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Trip;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


final class TripOwnerController extends AbstractController
{
    #[IsGranted('ROLE_DRIVER')]
#[Route('/trip/owner', name: 'app_trip_owner')]
public function index(EntityManagerInterface $entityManager): Response
{
    // Retrieve the authenticated user
    $user = $this->getUser();

    if (!$user instanceof \App\Entity\User) {
        return $this->json(['success' => false, 'me ssage' => 'Invalid user type'], 401);
    }

    // Retrieve the driver associated with the authenticated user
    $driver = $entityManager->getRepository(\App\Entity\Driver::class)->findOneBy(['user' => $user]);

    if (!$driver) {
        return $this->json(['success' => false, 'message' => 'Driver not found'], 404);
    }

    // Fetch trips for the authenticated driver
    $driverId = $driver->getId_driver();
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
    #[Route('trips/confirm-bookings', name: 'confirm_bookings', methods: ['POST'])]
    public function confirmBookings(Request $request, BookingRepository $bookingRepository, EntityManagerInterface $entityManager): Response
    {
        // Retrieve the trip ID from the request
        $tripId = $request->request->get('trip_id');

        // Fetch all bookings related to the trip
        $bookings = $bookingRepository->findBy(['trip' => $tripId]);

        // Update the status of each booking to "confirmed"
        foreach ($bookings as $booking) {
            $booking->setStatus('confirmed');
        }

        // Persist the changes to the database
        $entityManager->flush();

        // Redirect back to the DriverTripsManagement.twig template
        return $this->redirectToRoute('app_trip_owner');
    }
    #[Route('/carpooling/trip/create', name: 'carpooling_trip_create', methods: ['GET'])]
    public function createTrip(EntityManagerInterface $entityManager): Response
    {
        // Fetch cities (assuming cities are stored in the database or an enum)
        $cities = array_map(fn($zone) => $zone->value, \App\Enum\Zone::cases());

        return $this->render('front/carpooling/CarpoolingTripCreation.twig', [
            'cities' => $cities,
        ]);
    }
    #[Route('/api/predict-price', name: 'api_predict_price', methods: ['POST'])]
    public function predict(Request $request, PredictPrice $predictPrice, LoggerInterface $logger): JsonResponse
    {
        try {
            // Ensure it's an AJAX request
            if (!$request->isXmlHttpRequest()) {
                $logger->warning('Non-AJAX request received');
                return $this->fallbackResponse();
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->warning('Invalid JSON received', ['error' => json_last_error_msg()]);
                return $this->fallbackResponse();
            }

            $departureCity = $data['departureCity'] ?? null;
            $arrivalCity = $data['arrivalCity'] ?? null;
            $availableSeats = $data['availableSeats'] ?? 0;

            // Validate input data
            if (empty($departureCity) || empty($arrivalCity) || $availableSeats <= 0) {
                $logger->warning('Invalid input data', [
                    'departure' => $departureCity,
                    'arrival' => $arrivalCity,
                    'seats' => $availableSeats
                ]);
                return $this->fallbackResponse();
            }

            // Create and validate trip
            $trip = new Trip();
            $trip->setDepartureCity(trim($departureCity));
            $trip->setArrivalCity(trim($arrivalCity));
            $trip->setAvailableSeats((int)$availableSeats);

            try {
                $price = $predictPrice->predict($trip);

                if ($price === null) {
                    $logger->warning('Prediction service returned null');
                    return $this->fallbackResponse();
                }

                return new JsonResponse(['price' => round($price, 2)]);

            } catch (\Exception $e) {
                $logger->error('Prediction service failed', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->fallbackResponse();
            }

        } catch (\Exception $e) {
            $logger->critical('Unexpected error in prediction endpoint', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->fallbackResponse();
        }
    }

    private function fallbackResponse(): JsonResponse
    {
        // Generate random price between 7 and 10 with 2 decimal places
        $randomPrice = round(7 + (mt_rand() / mt_getrandmax() * 3), 2);
        return new JsonResponse(['price' => $randomPrice, 'warning' => 'Used fallback pricing']);
    }
    #[Route('/api/predict-price', methods: ['POST'])]
    public function testRoute(Request $request): JsonResponse
    {
        return new JsonResponse(['status' => 'ok', 'data' => $request->request->all()]);
    }

    #[Route('/driver/trip/create', name: 'app_driver_trip_create', methods: ['POST'])]
public function createTripForBooking(Request $request, EntityManagerInterface $entityManager): Response
{
    // Retrieve form data
    $departureCity = $request->request->get('departureCity');
    $arrivalCity = $request->request->get('arrivalCity');
    $departureDate = $request->request->get('departureDate');
    $departureTime = $request->request->get('departureTime');
    $availableSeats = $request->request->get('availableSeats');
    $pricePerPassenger = $request->request->get('pricePerPassenger');
    $notes = $request->request->get('notes');

    // Validation
    $errors = [];
    if (empty($departureCity)) {
        $errors[] = 'Departure city is required.';
    }
    if (empty($arrivalCity)) {
        $errors[] = 'Arrival city is required.';
    }
    if (empty($departureDate)) {
        $errors[] = 'Departure date is required.';
    }
    if (empty($departureTime)) {
        $errors[] = 'Departure time is required.';
    }
    if (empty($availableSeats)) {
        $errors[] = 'Available seats are required.';
    }
    if (empty($pricePerPassenger)) {
        $errors[] = 'Price per passenger is required.';
    }

    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error);
        }
        return $this->redirectToRoute('carpooling_trip_create'); // Adjust the route as needed
    }

    // Create a new Trip entity
    $trip = new Trip();
    $trip->setDepartureCity($departureCity);
    $trip->setArrivalCity($arrivalCity);
    $trip->setDepartureDate(new \DateTime($departureDate . ' ' . $departureTime));
    $trip->setAvailableSeats((int) $availableSeats);
    $trip->setPricePerPassenger((float) $pricePerPassenger);

    // Fetch the Driver and Vehicle entities
    $driver = $entityManager->getRepository(\App\Entity\Driver::class)->find(1);
    $vehicle = $entityManager->getRepository(\App\Entity\Vehicle::class)->find(1);

    if (!$driver || !$vehicle) {
        throw $this->createNotFoundException('Driver or Vehicle not found.');
    }

    // Set the Driver and Vehicle entities
    $trip->setDriver($driver);
    $trip->setVehicle($vehicle);

    // Persist and flush the new trip
    $entityManager->persist($trip);
    $entityManager->flush();

    // Add a success message and redirect
    $this->addFlash('success', 'Trip created successfully.');
    return $this->redirectToRoute('app_trip_owner');
}

    #[Route('/trip/delete/{id}', name: 'trip_delete', methods: ['POST'])]
    public function deleteTripById(int $id, TripService $tripService): Response
    {
        // Fetch the trip by ID
        $trip = $tripService->getTrip($id);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('app_trip_owner');
        }

        // Delete the trip
        $tripService->deleteTrip($trip);

        $this->addFlash('success', 'Trip deleted successfully.');

        // Redirect to the trip owner page
        return $this->redirectToRoute('app_trip_owner');
    }
#[Route('/driver/trip/edit/{id}', name: 'app_driver_trip_edit', methods: ['GET', 'POST'])]
public function editTrip(int $id, Request $request, TripService $tripService): Response
{
    // Fetch the trip by ID
    $trip = $tripService->getTrip($id);

    if (!$trip) {
        $this->addFlash('error', 'Trip not found.');
        return $this->redirectToRoute('app_trip_owner');
    }

    // Fetch cities for the dropdown
    $cities = array_map(fn($zone) => $zone->value, \App\Enum\Zone::cases());

    if ($request->isMethod('POST')) {
        // Retrieve form data
        $data = [
            'departure_city' => $request->request->get('departureCity'),
            'arrival_city' => $request->request->get('arrivalCity'),
            'departure_date' => $request->request->get('departureDate') . ' ' . $request->request->get('departureTime'),
            'available_seats' => $request->request->get('availableSeats'),
            'price_per_passenger' => $request->request->get('pricePerPassenger'),
        ];
        foreach ($data as $key => $value) {
            if (empty($value)) {
                $this->addFlash('error', ucfirst(str_replace('_', ' ', $key)) . ' cannot be empty.');
                return $this->redirectToRoute('app_driver_trip_edit', ['id' => $id]);
            }
        }

        try {
            // Update trip using TripService
            $tripService->updateTrip($trip, $data);

            $this->addFlash('success', 'Trip updated successfully.');
            return $this->redirectToRoute('app_trip_owner');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to update trip: ' . $e->getMessage());
        }
    }

    // Render the app_trip_owner template with trips and cities
    return $this->render('front/carpooling/DriverTripsManagement.twig', [
        'cities' => $cities,
    ]);
}






}