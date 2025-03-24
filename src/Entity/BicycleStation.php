<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\BicycleStationRepository;

#[ORM\Entity(repositoryClass: BicycleStationRepository::class)]
#[ORM\Table(name: 'bicycle_station')]
class BicycleStation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_station = null;

    public function getId_station(): ?int
    {
        return $this->id_station;
    }

    public function setId_station(int $id_station): self
    {
        $this->id_station = $id_station;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'bicycleStations')]
    #[ORM\JoinColumn(name: 'id_location', referencedColumnName: 'id_location')]
    private ?Location $location = null;

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $total_docks = null;

    public function getTotal_docks(): ?int
    {
        return $this->total_docks;
    }

    public function setTotal_docks(int $total_docks): self
    {
        $this->total_docks = $total_docks;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $available_docks = null;

    public function getAvailable_docks(): ?int
    {
        return $this->available_docks;
    }

    public function setAvailable_docks(int $available_docks): self
    {
        $this->available_docks = $available_docks;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $available_bikes = null;

    public function getAvailable_bikes(): ?int
    {
        return $this->available_bikes;
    }

    public function setAvailable_bikes(int $available_bikes): self
    {
        $this->available_bikes = $available_bikes;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $charging_bikes = null;

    public function getCharging_bikes(): ?int
    {
        return $this->charging_bikes;
    }

    public function setCharging_bikes(int $charging_bikes): self
    {
        $this->charging_bikes = $charging_bikes;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $status = null;

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Bicycle::class, mappedBy: 'bicycleStation')]
    private Collection $bicycles;

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

    #[ORM\OneToMany(targetEntity: BicycleRental::class, mappedBy: 'bicycleStation')]
    private Collection $bicycleRentals;

    public function __construct()
    {
        $this->bicycles = new ArrayCollection();
        $this->bicycleRentals = new ArrayCollection();
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

    public function getIdStation(): ?int
    {
        return $this->id_station;
    }

    public function getTotalDocks(): ?int
    {
        return $this->total_docks;
    }

    public function setTotalDocks(int $total_docks): static
    {
        $this->total_docks = $total_docks;

        return $this;
    }

    public function getAvailableDocks(): ?int
    {
        return $this->available_docks;
    }

    public function setAvailableDocks(int $available_docks): static
    {
        $this->available_docks = $available_docks;

        return $this;
    }

    public function getAvailableBikes(): ?int
    {
        return $this->available_bikes;
    }

    public function setAvailableBikes(int $available_bikes): static
    {
        $this->available_bikes = $available_bikes;

        return $this;
    }

    public function getChargingBikes(): ?int
    {
        return $this->charging_bikes;
    }

    public function setChargingBikes(int $charging_bikes): static
    {
        $this->charging_bikes = $charging_bikes;

        return $this;
    }
}