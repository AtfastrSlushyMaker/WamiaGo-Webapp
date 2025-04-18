<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Driver;
use App\Enum\ReservationStatus;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Entity\Relocation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ReservationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository
    ) {}

    public function getReservationsByDriver(Driver $driver): array
    {
        return $this->reservationRepository->findByDriver($driver);
    }

    public function getReservationsQueryByDriver(Driver $driver): QueryBuilder
    {
        return $this->reservationRepository->getQueryByDriver($driver);
    }

    public function getReservationDetails(Reservation $reservation): array
    {
        return [
            'id' => $reservation->getIdReservation(),
            'description' => $reservation->getDescription(),
            'date' => $reservation->getDate()->format('Y-m-d H:i'),
            'status' => $reservation->getStatus()->value,
            'startLocation' => $reservation->getStartLocation()->getAddress(),
            'endLocation' => $reservation->getEndLocation()->getAddress(),
            'client' => [
                'name' => $reservation->getUser()->getName(),
                'email' => $reservation->getUser()->getEmail()
            ],
            'announcement' => [
                'title' => $reservation->getAnnouncement()->getTitle(),
                'zone' => $reservation->getAnnouncement()->getZone()->value
            ]
        ];
    }

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

        $this->entityManager->persist($relocation);
        $this->entityManager->flush();

        return $relocation;
    }

    public function refuseReservation(Reservation $reservation): void
    {
        if ($reservation->getStatus() !== ReservationStatus::ON_GOING) {
            throw new \InvalidArgumentException('Only ongoing reservations can be refused');
        }

        $reservation->setStatus(ReservationStatus::CANCELLED);
        $this->entityManager->flush();
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