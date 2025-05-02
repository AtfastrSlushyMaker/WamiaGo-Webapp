<?php

namespace App\Controller\Admin;

use App\Entity\Trip;
use App\Repository\TripRepository;
use App\Service\TripService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'rideShares' => 150,
            'taxiBookings' => 53,
            'bicycleRentals' => 44,
            'relocationBookings' => 65,
        ];

        return $this->render('back-office/dashboard.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function users(): Response
    {
        return $this->render('back-office/users.html.twig');
    }

    #[Route('/admin/ride-sharing', name: 'admin_ride_sharing')]
    public function rideSharing(EntityManagerInterface $entityManager): Response
    {
        // Fetch all trips from the database
        $trips = $entityManager->getRepository(Trip::class)->findAll();

        // Pass the trips to the template
        return $this->render('back-office/ride-sharing.html.twig', [
            'trips' => $trips,
        ]);
    }
    #[Route('/admin/trips/chart', name: 'admin_trips_chart')]
    public function tripsChart(TripRepository $tripRepository): Response
    {
        // Récupérer les données des trips groupées par mois
        $tripsByMonth = $tripRepository->countTripsByMonth();

        // Préparer les données pour le graphique
        $labels = [];
        $data = [];
        foreach ($tripsByMonth as $month => $count) {
            $labels[] = $month;
            $data[] = $count;
        }

        return $this->render('back-office/trips_chart.twig', [
            'labels' => json_encode($labels),
            'data' => json_encode($data),
        ]);
    }
    #[Route('/admin/trip/delete/{id}', name: 'admin_trip_delete', methods: ['POST'])]
    public function deleteTrip(int $id, EntityManagerInterface $entityManager, TripService $tripService): Response
    {
        $trip = $entityManager->getRepository(Trip::class)->find($id);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('admin_ride_sharing');
        }

        $tripService->deleteTrip($trip);

        $this->addFlash('success', 'Trip deleted successfully.');
        return $this->redirectToRoute('admin_ride_sharing');
    }

    #[Route('/admin/taxi-bookings', name: 'admin_taxi_bookings')]
    public function taxiBookings(): Response
    {
        return $this->render('back-office/taxi-bookings.html.twig');
    }

    #[Route('/admin/bicycle-rentals', name: 'admin_bicycle_rentals')]
    public function bicycleRentals(): Response
    {
        return $this->render('back-office/bicycle-rentals.html.twig');
    }

    #[Route('/admin/relocations', name: 'admin_relocations')]
    public function relocations(): Response
    {
        return $this->render('back-office/relocations.html.twig');
    }

    #[Route('/admin/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        return $this->render('back-office/settings.html.twig');
    }

    #[Route('/admin/profile', name: 'admin_profile')]
    public function profile(): Response
    {
        return $this->render('back-office/profile.html.twig');
    }
}