<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\BookingRepository;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_booking = null;

    public function getId_booking(): ?int
    {
        return $this->id_booking;
    }

    public function setId_booking(int $id_booking): self
    {
        $this->id_booking = $id_booking;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Trip::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'id_trip', referencedColumnName: 'id_trip')]
    private ?Trip $trip = null;

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'id_passenger', referencedColumnName: 'id_user')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $reserved_seats = null;

    public function getReserved_seats(): ?int
    {
        return $this->reserved_seats;
    }

    public function setReserved_seats(int $reserved_seats): self
    {
        $this->reserved_seats = $reserved_seats;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getIdBooking(): ?int
    {
        return $this->id_booking;
    }

    public function getReservedSeats(): ?int
    {
        return $this->reserved_seats;
    }

    public function setReservedSeats(int $reserved_seats): static
    {
        $this->reserved_seats = $reserved_seats;

        return $this;
    }

}
