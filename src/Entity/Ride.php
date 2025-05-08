<?php

namespace App\Entity;

use App\Enum\RIDE_STATUS;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\RideRepository;

#[ORM\Entity(repositoryClass: RideRepository::class)]
#[ORM\Table(name: 'ride')]
class Ride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_ride = null;

    public function getId_ride(): ?int
    {
        return $this->id_ride;
    }

    public function setId_ride(int $id_ride): self
    {
        $this->id_ride = $id_ride;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Request::class, inversedBy: 'rides')]
    #[ORM\JoinColumn(name: 'id_request', referencedColumnName: 'id_request')]
    private ?Request $request = null;

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $distance = null;

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $price = null;

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    #[ORM\Column(enumType: RIDE_STATUS::class)]
    private ?RIDE_STATUS $status = null;

    public function getStatus(): ?RIDE_STATUS
    {
        return $this->status;
    }

    public function setStatus(RIDE_STATUS $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $ride_date = null;

    public function getRide_date(): ?\DateTimeInterface
    {
        return $this->ride_date;
    }

    public function setRide_date(\DateTimeInterface $ride_date): self
    {
        $this->ride_date = $ride_date;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Driver::class, inversedBy: 'rides')]
    #[ORM\JoinColumn(name: 'id_taxi', referencedColumnName: 'id_driver')]
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

    public function getIdRide(): ?int
    {
        return $this->id_ride;
    }

    public function getRideDate(): ?\DateTimeInterface
    {
        return $this->ride_date;
    }

    public function setRideDate(\DateTimeInterface $ride_date): static
    {
        $this->ride_date = $ride_date;
        return $this;
    }
}