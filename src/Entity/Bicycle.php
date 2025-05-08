<?php

namespace App\Entity;

use App\Enum\BICYCLE_STATUS;
use App\Repository\BicycleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BicycleRepository::class)]
#[ORM\Table(name: 'bicycle')]
class Bicycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idBike = null;

    #[ORM\ManyToOne(targetEntity: BicycleStation::class, inversedBy: 'bicycles')]
    #[ORM\JoinColumn(name: 'id_station', referencedColumnName: 'id_station')]
    private ?BicycleStation $bicycleStation = null;

    #[ORM\Column(enumType: BICYCLE_STATUS::class)]
    #[Assert\NotNull(message: 'Status is required.')]
    private ?BICYCLE_STATUS $status = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotNull(message: 'Battery level is required.')]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'Battery level must be between {{ min }} and {{ max }}%.'
    )]
    private ?float $batteryLevel = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotNull(message: 'Range is required.')]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: 'Range must be a positive number.'
    )]
    private ?float $rangeKm = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: 'Last updated is required.')]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $lastUpdated = null;

    #[ORM\OneToMany(targetEntity: BicycleRental::class, mappedBy: 'bicycle')]
    private Collection $bicycleRentals;

    public function __construct()
    {
        $this->bicycleRentals = new ArrayCollection();
    }

    public function getIdBike(): ?int
    {
        return $this->idBike;
    }

    public function setIdBike(int $idBike): self
    {
        $this->idBike = $idBike;
        return $this;
    }

    public function getBicycleStation(): ?BicycleStation
    {
        return $this->bicycleStation;
    }

    public function setBicycleStation(?BicycleStation $bicycleStation): self
    {
        $this->bicycleStation = $bicycleStation;
        return $this;
    }

    public function getStatus(): ?BICYCLE_STATUS
    {
        return $this->status;
    }

    public function setStatus(BICYCLE_STATUS $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getBatteryLevel(): ?float
    {
        return $this->batteryLevel;
    }

    public function setBatteryLevel(?float $batteryLevel): self
    {
        $this->batteryLevel = $batteryLevel;
        return $this;
    }

    public function getRangeKm(): ?float
    {
        return $this->rangeKm;
    }

    public function setRangeKm(?float $rangeKm): self
    {
        $this->rangeKm = $rangeKm;
        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    /**
     * @return Collection<int, BicycleRental>
     */
    public function getBicycleRentals(): Collection
    {
        return $this->bicycleRentals;
    }

    public function addBicycleRental(BicycleRental $bicycleRental): self
    {
        if (!$this->bicycleRentals->contains($bicycleRental)) {
            $this->bicycleRentals[] = $bicycleRental;
        }

        return $this;
    }

    public function removeBicycleRental(BicycleRental $bicycleRental): self
    {
        $this->bicycleRentals->removeElement($bicycleRental);
        return $this;
    }
}
