<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Relocation;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DriverRepository;

class ReservationService
{
    private const HARDCODED_DRIVER_ID = 6;

    public function __construct(
        private EntityManagerInterface $em,
        private DriverRepository $driverRepo
    ) {}

    public function getTransporterReservations(): array
    {
        $driver = $this->driverRepo->find(self::HARDCODED_DRIVER_ID);
        
        if (!$driver) {
            throw new \RuntimeException('Driver not found');
        }

        return $this->em->getRepository(Reservation::class)
            ->findByDriver($driver);
    }

    public function confirmReservation(Reservation $reservation): void
    {
        $reservation->setStatus(ReservationStatus::CONFIRMED);
        $this->em->flush();
    }

    public function cancelReservation(Reservation $reservation): void
    {
        $reservation->setStatus(ReservationStatus::CANCELLED);
        $this->em->flush();
    }
}