<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\StatsService;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    #[Route('/', name: 'admin_stats_index')]
    public function index(StatsService $statsService): Response
    {
        $totalUsers = $statsService->getTotalUsers();
        $totalAnnouncements = $statsService->getTotalAnnouncements();
        $totalRelocations = $statsService->getTotalRelocations();
        $totalReservations = $statsService->getTotalReservations();
        
        $reservationsByStatus = $statsService->getReservationsByStatus();
        $reservationsPercentage = $statsService->getReservationsPercentage();

        return $this->render('back-office/Stats/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAnnouncements' => $totalAnnouncements,
            'totalRelocations' => $totalRelocations,
            'totalReservations' => $totalReservations,
            'reservationsByStatus' => $reservationsByStatus,
            'reservationsPercentage' => $reservationsPercentage
        ]);
    }
}