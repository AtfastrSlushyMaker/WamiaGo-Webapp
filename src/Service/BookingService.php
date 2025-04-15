<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $bookingRepository;

    public function __construct(EntityManagerInterface $entityManager, BookingRepository $bookingRepository)
    {
        $this->entityManager = $entityManager;
        $this->bookingRepository = $bookingRepository;
    }

    // Fetch a booking by ID
    public function getBooking(int $id): ?Booking
    {
        return $this->bookingRepository->find($id);
    }

    // Create a new booking
    public function createBooking(array $data): Booking
    {
        $booking = new Booking();
        $booking->setTrip($data['trip']);
        $booking->setUser($data['user']);
        $booking->setReservedSeats($data['reserved_seats']);
        $booking->setStatus($data['status']);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    // Update an existing booking
    public function updateBooking(Booking $booking, array $data): Booking
    {
        if (isset($data['trip'])) {
            $booking->setTrip($data['trip']);
        }
        if (isset($data['user'])) {
            $booking->setUser($data['user']);
        }
        if (isset($data['reserved_seats'])) {
            $booking->setReservedSeats($data['reserved_seats']);
        }
        if (isset($data['status'])) {
            $booking->setStatus($data['status']);
        }

        $this->entityManager->flush();

        return $booking;
    }

    // Delete a booking
    public function deleteBooking(Booking $booking): void
    {
        $this->entityManager->remove($booking);
        $this->entityManager->flush();
    }

    // Fetch all bookings
    public function getAllBookings(): array
    {
        return $this->bookingRepository->findAll();
    }
}