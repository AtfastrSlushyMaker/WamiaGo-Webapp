<?php


namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Trip;
use App\Entity\User;
use App\Service\TripService;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // Ensure this is included
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
final class CarpoolingController extends AbstractController
{
    private TripService $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }

    #[Route('/carpooling', name: 'app_carpooling')]
    public function index(): Response
    {
        $trips = $this->tripService->getAllTrips();

        return $this->render('front/carpooling/carpoolingMain.twig', [
            'controller_name' => 'CarpoolingController',
            'trips' => $trips,
        ]);
    }

    #[Route('/booking/details/{id}', name: 'app_front_show_booking_details')]
    public function showBookingDetails(int $id, EntityManagerInterface $entityManager): Response
    {
        // Fetch booking details using the EntityManager
        $booking = $entityManager->getRepository(Booking::class)->find($id);

        if (!$booking) {
            throw $this->createNotFoundException('Booking not found.');
        }

        return $this->render('front/carpooling/carpoolingConfirm.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/booking/cancel/{id}', name: 'app_front_cancel_booking')]
    public function deleteBooking(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Fetch the booking by ID
        $booking = $entityManager->getRepository(Booking::class)->find($id);

        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_front_show_booking_details', ['id' => $id]);
        }

        // Remove the booking from the database
        $entityManager->remove($booking);
        $entityManager->flush();

        $this->addFlash('success', 'Booking has been successfully deleted.');
        return $this->redirectToRoute('app_carpooling');
    }

    #[Route('/trip/confirm/{id}', name: 'app_trip_confirm', methods: ['GET', 'POST'])]
    public function tripConfirmPage(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Fetch the trip by ID
        $trip = $entityManager->getRepository(Trip::class)->find($id);

        if (!$trip) {
            throw $this->createNotFoundException('Trip not found.');
        }

        // Retrieve selected seats and payment method from the request

        $selectedSeats = $request->get('selected_seats', []); // Default to an empty array if not provided
        $paymentMethod = $request->get('payment_method', 'presential'); // Default to 'presential'
        $totalAmount = count($selectedSeats) * $trip->getPricePerPassenger();
        // Fetch all bookings for the trip
        $bookings = $entityManager->getRepository(Booking::class)->findBy(['trip' => $trip]);

        // Update booking statuses based on the payment method
        foreach ($bookings as $booking) {
            if ($paymentMethod === 'online') {
                $booking->setStatus('Confirmed');
            } elseif ($paymentMethod === 'presential') {
                $booking->setStatus('Pending');
            }
        }

        // Persist changes to the database
        $entityManager->flush();

        // Calculate unavailable seats
        $unavailableSeats = [];
        foreach ($bookings as $booking) {
            $reservedSeats = explode(',', $booking->getReservedSeats()); // Assuming reserved seats are stored as a comma-separated string
            $unavailableSeats = array_merge($unavailableSeats, $reservedSeats);
        }

        // Ensure unique seat numbers
        $unavailableSeats = array_unique($unavailableSeats);

        // Render the Trip Confirm Page
        return $this->render('front/carpooling/tripConfirmPage.twig', [
            'selected_seats' => $selectedSeats,
            'price_per_passenger' => $trip->getPricePerPassenger(),
            'trip' => $trip,
            'unavailable_seats' => $unavailableSeats,
            'payment_method' => $paymentMethod,
            'total_amount' => $totalAmount, // Pass the calculated total amount

        ]);
    }

    #[Route('/trip/reserve', name: 'app_trip_reserve', methods: ['POST'])]
    public function reserveSeats(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $tripId = $request->request->get('trip_id');
        $selectedSeats = explode(',', $request->request->get('selected_seats'));

        $trip = $entityManager->getRepository(Trip::class)->find($tripId);

        if (!$trip) {
            return $this->json(['error' => 'Trip not found.'], 404);
        }

        if (count($selectedSeats) > $trip->getAvailableSeats()) {
            return $this->json(['error' => 'You cannot reserve more seats than available.'], 400);
        }

        // Update available seats
        $trip->setAvailableSeats($trip->getAvailableSeats() - count($selectedSeats));
        $entityManager->flush();

        return $this->json(['success' => 'Seats reserved successfully.']);
    }

    #[Route('/booking/confirm/{id}', name: 'app_front_confirm_booking', methods: ['POST'])]
    public function confirmBooking(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Validate the ID parameter
        if (!$id) {
            return $this->json(['error' => 'Missing booking ID.'], 400);
        }

        // Fetch the booking by ID
        $booking = $entityManager->getRepository(Booking::class)->find($id);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found.'], 404);
        }

        // Update the booking status to "Confirmed"
        $booking->setStatus('Confirmed');
        $entityManager->flush();

        return $this->json(['success' => 'Booking status updated to Confirmed.']);
    }
    #[Route('/trip/select-seats/{id}', name: 'app_front_select_seats', methods: ['POST'])]
    public function selectSeats(int $id, Request $request, EntityManagerInterface $entityManager, BookingService $bookingService): Response
    {
        // Fetch the trip by ID
        $trip = $entityManager->getRepository(Trip::class)->find($id);

        if (!$trip) {
            throw $this->createNotFoundException('Trip not found.');
        }

        // Retrieve selected seats from the request
        $selectedSeats = $request->request->get('selected_seats', '');

        // Convert the string to an array if it's not already an array
        if (is_string($selectedSeats)) {
            $selectedSeats = explode(',', $selectedSeats);
        }

        if (empty($selectedSeats) || !is_array($selectedSeats)) {
            $this->addFlash('error', 'No seats selected.');
            return $this->redirectToRoute('app_carpooling');
        }
        $user = $entityManager->getRepository(User::class)->find(1);
        if (!$user) {
            throw $this->createNotFoundException('Static user with ID 1 not found.');
        }


        // Prepare booking data
        $bookingData = [
            'trip' => $trip,
            'user' => $user, // Assuming the user is logged in
            'reserved_seats' => count($selectedSeats), // Pass the count of selected seats
            'status' => 'Pending',
        ];

        // Create the booking
        $bookingService->createBooking($bookingData);

        // Update the trip's available seats
        $trip->setAvailableSeats($trip->getAvailableSeats() - count($selectedSeats));
        $entityManager->flush();

        $this->addFlash('success', 'Booking created successfully.');

        // Redirect to the carpooling main page
        return $this->redirectToRoute('app_carpooling');
    }


}