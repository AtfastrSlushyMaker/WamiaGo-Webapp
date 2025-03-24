<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TripRepository;

#[ORM\Entity(repositoryClass: TripRepository::class)]
#[ORM\Table(name: 'trip')]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_trip = null;

    public function getId_trip(): ?int
    {
        return $this->id_trip;
    }

    public function setId_trip(int $id_trip): self
    {
        $this->id_trip = $id_trip;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $departure_city = null;

    public function getDeparture_city(): ?string
    {
        return $this->departure_city;
    }

    public function setDeparture_city(string $departure_city): self
    {
        $this->departure_city = $departure_city;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $arrival_city = null;

    public function getArrival_city(): ?string
    {
        return $this->arrival_city;
    }

    public function setArrival_city(string $arrival_city): self
    {
        $this->arrival_city = $arrival_city;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $departure_date = null;

    public function getDeparture_date(): ?\DateTimeInterface
    {
        return $this->departure_date;
    }

    public function setDeparture_date(\DateTimeInterface $departure_date): self
    {
        $this->departure_date = $departure_date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $available_seats = null;

    public function getAvailable_seats(): ?int
    {
        return $this->available_seats;
    }

    public function setAvailable_seats(int $available_seats): self
    {
        $this->available_seats = $available_seats;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $price_per_passenger = null;

    public function getPrice_per_passenger(): ?float
    {
        return $this->price_per_passenger;
    }

    public function setPrice_per_passenger(?float $price_per_passenger): self
    {
        $this->price_per_passenger = $price_per_passenger;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Driver::class, inversedBy: 'trips')]
    #[ORM\JoinColumn(name: 'id_driver', referencedColumnName: 'id_driver')]
    private ?Driver $driver = null;

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Vehicle::class, inversedBy: 'trips')]
    #[ORM\JoinColumn(name: 'id_vehicle', referencedColumnName: 'id_vehicle')]
    private ?Vehicle $vehicle = null;

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'trip')]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        if (!$this->bookings instanceof Collection) {
            $this->bookings = new ArrayCollection();
        }
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->getBookings()->contains($booking)) {
            $this->getBookings()->add($booking);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        $this->getBookings()->removeElement($booking);
        return $this;
    }

    public function getIdTrip(): ?int
    {
        return $this->id_trip;
    }

    public function getDepartureCity(): ?string
    {
        return $this->departure_city;
    }

    public function setDepartureCity(string $departure_city): static
    {
        $this->departure_city = $departure_city;

        return $this;
    }

    public function getArrivalCity(): ?string
    {
        return $this->arrival_city;
    }

    public function setArrivalCity(string $arrival_city): static
    {
        $this->arrival_city = $arrival_city;

        return $this;
    }

    public function getDepartureDate(): ?\DateTimeInterface
    {
        return $this->departure_date;
    }

    public function setDepartureDate(\DateTimeInterface $departure_date): static
    {
        $this->departure_date = $departure_date;

        return $this;
    }

    public function getAvailableSeats(): ?int
    {
        return $this->available_seats;
    }

    public function setAvailableSeats(int $available_seats): static
    {
        $this->available_seats = $available_seats;

        return $this;
    }

    public function getPricePerPassenger(): ?string
    {
        return $this->price_per_passenger;
    }

    public function setPricePerPassenger(?string $price_per_passenger): static
    {
        $this->price_per_passenger = $price_per_passenger;

        return $this;
    }

}
