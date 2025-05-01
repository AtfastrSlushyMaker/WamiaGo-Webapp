<?php

namespace App\Entity;

use App\Enum\BICYCLE_STATION_STATUS;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\BicycleStationRepository;

#[ORM\Entity(repositoryClass: BicycleStationRepository::class)]
#[ORM\Table(name: 'bicycle_station')]
class BicycleStation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_station = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Station name cannot be empty")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Station name must be at least {{ limit }} characters long",
        maxMessage: "Station name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'bicycleStations')]
    #[ORM\JoinColumn(name: 'id_location', referencedColumnName: 'id_location')]
    #[Assert\NotNull(message: "Station must have a location")]
    private ?Location $location = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "Total docks cannot be empty")]
    #[Assert\Positive(message: "Total docks must be greater than zero")]
    private ?int $total_docks = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: "Available docks cannot be negative"
    )]
    private ?int $available_docks = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: "Available bikes cannot be negative"
    )]
    #[Assert\Expression(
        "this.getAvailableBikes() <= this.getTotalDocks()",
        message: "Available bikes cannot exceed total docks"
    )]
    private ?int $available_bikes = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: "Charging bikes cannot be negative"
    )]
    private ?int $charging_bikes = null;

    #[ORM\Column(enumType: BICYCLE_STATION_STATUS::class)]
    #[Assert\NotNull(message: "Station status is required")]
    private ?BICYCLE_STATION_STATUS $status = null;

    #[ORM\OneToMany(targetEntity: Bicycle::class, mappedBy: 'bicycleStation')]
    private Collection $bicycles;

    #[ORM\OneToMany(targetEntity: BicycleRental::class, mappedBy: 'bicycleStation')]
    private Collection $bicycleRentals;

    public function __construct()
    {
        $this->bicycles = new ArrayCollection();
        $this->bicycleRentals = new ArrayCollection();
    }

    public function getIdStation(): ?int
    {
        return $this->id_station;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getTotalDocks(): ?int
    {
        return $this->total_docks;
    }

    public function setTotalDocks(int $total_docks): self
    {
        $this->total_docks = $total_docks;
        return $this;
    }

    public function getAvailableDocks(): ?int
    {
        return $this->available_docks;
    }

    public function setAvailableDocks(int $available_docks): self
    {
        $this->available_docks = $available_docks;
        return $this;
    }

    public function getAvailableBikes(): ?int
    {
        return $this->available_bikes;
    }

    public function setAvailableBikes(int $available_bikes): self
    {
        $this->available_bikes = $available_bikes;
        return $this;
    }

    public function getChargingBikes(): ?int
    {
        return $this->charging_bikes;
    }

    public function setChargingBikes(int $charging_bikes): self
    {
        $this->charging_bikes = $charging_bikes;
        return $this;
    }

    public function getStatus(): ?BICYCLE_STATION_STATUS
    {
        return $this->status;
    }

    public function setStatus(BICYCLE_STATION_STATUS $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return Collection<int, Bicycle>
     */
    public function getBicycles(): Collection
    {
        if (!$this->bicycles instanceof Collection) {
            $this->bicycles = new ArrayCollection();
        }
        return $this->bicycles;
    }

    public function addBicycle(Bicycle $bicycle): self
    {
        if (!$this->getBicycles()->contains($bicycle)) {
            $this->getBicycles()->add($bicycle);
        }
        return $this;
    }

    public function removeBicycle(Bicycle $bicycle): self
    {
        $this->getBicycles()->removeElement($bicycle);
        return $this;
    }

    /**
     * @return Collection<int, BicycleRental>
     */
    public function getBicycleRentals(): Collection
    {
        if (!$this->bicycleRentals instanceof Collection) {
            $this->bicycleRentals = new ArrayCollection();
        }
        return $this->bicycleRentals;
    }

    public function addBicycleRental(BicycleRental $bicycleRental): self
    {
        if (!$this->getBicycleRentals()->contains($bicycleRental)) {
            $this->getBicycleRentals()->add($bicycleRental);
        }
        return $this;
    }

    public function removeBicycleRental(BicycleRental $bicycleRental): self
    {
        $this->getBicycleRentals()->removeElement($bicycleRental);
        return $this;
    }
}