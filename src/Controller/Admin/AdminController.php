<?php

namespace App\Controller\Admin;

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
    public function rideSharing(): Response
    {
        return $this->render('back-office/ride-sharing.html.twig');
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
    #[Route('/admin/contact', name: 'admin_contact')]
    public function contact(): Response
    {
        return $this->render('back-office/contact.html.twig');
    }
#[Route('/admin/response', name: 'admin_response')]
    public function response(): Response
    {
        return $this->render('back-office/response.html.twig');
    }




    
}