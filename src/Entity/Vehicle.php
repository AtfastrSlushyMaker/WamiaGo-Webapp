<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VehicleRepository;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\Table(name: 'vehicle')]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_vehicle = null;

    public function getId_vehicle(): ?int
    {
        return $this->id_vehicle;
    }

    public function setId_vehicle(int $id_vehicle): self
    {
        $this->id_vehicle = $id_vehicle;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Driver::class, inversedBy: 'vehicles')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $registration = null;

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function setRegistration(string $registration): self
    {
        $this->registration = $registration;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $color = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $model = null;

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $brand = null;

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'vehicle')]
    private Collection $trips;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        if (!$this->trips instanceof Collection) {
            $this->trips = new ArrayCollection();
        }
        return $this->trips;
    }

    public function addTrip(Trip $trip): self
    {
        if (!$this->getTrips()->contains($trip)) {
            $this->getTrips()->add($trip);
        }
        return $this;
    }

    public function removeTrip(Trip $trip): self
    {
        $this->getTrips()->removeElement($trip);
        return $this;
    }

    public function getIdVehicle(): ?int
    {
        return $this->id_vehicle;
    }

}
