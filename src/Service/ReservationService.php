<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Driver;
use App\Enum\ReservationStatus;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Entity\Relocation;

class ReservationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private \Doctrine\ORM\EntityManagerInterface $em
    ) {}

    public function getReservationsByDriver(Driver $driver): array
    {
        return $this->reservationRepository->findByDriver($driver);
    }

    public function getReservationDetails(Reservation $reservation): array
    {
        return [
            'title' => $reservation->getAnnouncement()->getTitle(),
            'description' => $reservation->getDescription(),
            'date' => $reservation->getDate()->format('d M Y, H:i'),
            'status' => $reservation->getStatus()->value,
            'startLocation' => $reservation->getStartLocation()->getAddress(),
            'endLocation' => $reservation->getEndLocation()->getAddress(),
            'client' => $reservation->getUser()->getName()
        ];
    }
  

   // src/Service/ReservationService.php
public function acceptReservation(Reservation $reservation, \DateTimeInterface $date, float $cost): Relocation
{
    if ($reservation->getStatus() !== ReservationStatus::ON_GOING) {
        throw new \InvalidArgumentException('Only ongoing reservations can be accepted');
    }

    $relocation = new Relocation();
    $relocation->setReservation($reservation);
    $relocation->setDate($date);
    $relocation->setCost($cost);
    $relocation->setStatus(true);

    $reservation->setStatus(ReservationStatus::CONFIRMED);

    $this->em->persist($relocation);
    $this->em->flush();

    return $relocation;
}

public function refuseReservation(Reservation $reservation): void
{
    if ($reservation->getStatus() !== ReservationStatus::ON_GOING) {
        throw new \InvalidArgumentException('Only ongoing reservations can be refused');
    }

    $reservation->setStatus(ReservationStatus::CANCELLED);
    $this->em->flush();
}



public function getReservationsByUser(User $user): array
{
    return $this->reservationRepository->findBy([
        'user' => $user
    ], ['date' => 'DESC']);
}

public function getClientReservationDetails(Reservation $reservation): array
{
    return [
        'title' => $reservation->getAnnouncement()->getTitle(),
        'description' => $reservation->getDescription(),
        'date' => $reservation->getDate()->format('d M Y, H:i'),
        'status' => $reservation->getStatus()->value,
        'startLocation' => $reservation->getStartLocation()->getAddress(),
        'endLocation' => $reservation->getEndLocation()->getAddress()
    ];
}

    
}