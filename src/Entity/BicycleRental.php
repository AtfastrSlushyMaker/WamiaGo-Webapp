<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\BicycleRentalRepository;
use App\Entity\User;
use App\Entity\Bicycle;
use App\Entity\BicycleStation;


#[ORM\Entity(repositoryClass: BicycleRentalRepository::class)]
#[ORM\Table(name: 'bicycle_rental')]
class BicycleRental
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_user_rental = null;

    public function getId_user_rental(): ?int
    {
        return $this->id_user_rental;
    }

    public function setId_user_rental(int $id_user_rental): self
    {
        $this->id_user_rental = $id_user_rental;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bicycleRentals')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
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

    #[ORM\ManyToOne(targetEntity: Bicycle::class, inversedBy: 'bicycleRentals')]
    #[ORM\JoinColumn(name: 'id_bike', referencedColumnName: 'id_bike')]
    private ?Bicycle $bicycle = null;

    public function getBicycle(): ?Bicycle
    {
        return $this->bicycle;
    }

    public function setBicycle(?Bicycle $bicycle): self
    {
        $this->bicycle = $bicycle;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: BicycleStation::class, inversedBy: 'bicycleRentals')]
    #[ORM\JoinColumn(name: 'id_start_station', referencedColumnName: 'id_station')]
    private ?BicycleStation $start_station = null;

    public function getStartBicycleStation(): ?BicycleStation
    {
        return $this->start_station;
    }

    public function setStartStation(?BicycleStation $bicycleStation): self
    {
        $this->start_station = $bicycleStation;
        return $this;
    }


    #[ORM\ManyToOne(targetEntity: BicycleStation::class, inversedBy: 'bicycleRentals')]
    #[ORM\JoinColumn(name: 'id_end_station', referencedColumnName: 'id_station')]
    private ?BicycleStation $end_station = null;

    public function getEndStation(): ?BicycleStation
    {
        return $this->end_station;
    }
    public function getStartStation(): ?BicycleStation
    {
        return $this->start_station;
    }

    public function setEndStation(?BicycleStation $end_station): void
    {
        $this->end_station = $end_station;
    }

    public function getStart_station(): ?BicycleStation
    {
        return $this->start_station;
    }
    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $start_time = null;

    public function getStart_time(): ?\DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStart_time(\DateTimeInterface $start_time): self
    {
        $this->start_time = $start_time;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $end_time = null;

    public function getEnd_time(): ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEnd_time(?\DateTimeInterface $end_time): self
    {
        $this->end_time = $end_time;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $distance_km = null;

    public function getDistance_km(): ?float
    {
        return $this->distance_km;
    }

    public function setDistance_km(float $distance_km): self
    {
        $this->distance_km = $distance_km;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $battery_used = null;

    public function getBattery_used(): ?float
    {
        return $this->battery_used;
    }

    public function setBattery_used(float $battery_used): self
    {
        $this->battery_used = $battery_used;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $cost = null;

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function getIdUserRental(): ?int
    {
        return $this->id_user_rental;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStartTime(\DateTimeInterface $start_time): static
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEndTime(?\DateTimeInterface $end_time): static
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getDistanceKm(): ?float
    {
        return $this->distance_km;
    }

    public function setDistanceKm(float $distance_km): static
    {
        $this->distance_km = $distance_km;

        return $this;
    }

    public function getBatteryUsed(): ?float
    {
        return $this->battery_used;
    }

    public function setBatteryUsed(float $battery_used): static
    {
        $this->battery_used = $battery_used;

        return $this;
    }
}
