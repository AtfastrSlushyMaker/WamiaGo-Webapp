<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\AnnouncementRepository;
use App\Repository\RelocationRepository;
use App\Repository\ReservationRepository;
use App\Enum\ReservationStatus;

class StatsService
{
    private $userRepo;
    private $announcementRepo;
    private $relocationRepo;
    private $reservationRepo;

    public function __construct(
        UserRepository $userRepo,
        AnnouncementRepository $announcementRepo,
        RelocationRepository $relocationRepo,
        ReservationRepository $reservationRepo
    ) {
        $this->userRepo = $userRepo;
        $this->announcementRepo = $announcementRepo;
        $this->relocationRepo = $relocationRepo;
        $this->reservationRepo = $reservationRepo;
    }

    public function getTotalUsers(): int
    {
        return $this->userRepo->count([]);
    }

    public function getTotalAnnouncements(): int
    {
        return $this->announcementRepo->count([]);
    }

    public function getTotalRelocations(): int
    {
        return $this->relocationRepo->count([]);
    }

    public function getTotalReservations(): int
    {
        return $this->reservationRepo->count([]);
    }

    public function getReservationsByStatus(): array
    {
        return [
            'ongoing' => $this->reservationRepo->count(['status' => ReservationStatus::ON_GOING]),
            'completed' => $this->reservationRepo->count(['status' => ReservationStatus::COMPLETED]),
            'canceled' => $this->reservationRepo->count(['status' => ReservationStatus::CANCELLED]),
            'confirmed' => $this->reservationRepo->count(['status' => ReservationStatus::CONFIRMED])
        ];
    }

    public function getReservationsPercentage(): array
    {
        $total = $this->getTotalReservations();
        $statusCounts = $this->getReservationsByStatus();

        return [
            'ongoing' => $total > 0 ? ($statusCounts['ongoing'] / $total) * 100 : 0,
            'completed' => $total > 0 ? ($statusCounts['completed'] / $total) * 100 : 0,
            'canceled' => $total > 0 ? ($statusCounts['canceled'] / $total) * 100 : 0,
            'confirmed' => $total > 0 ? ($statusCounts['confirmed'] / $total) * 100 : 0
        ];
    }
}