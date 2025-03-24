<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\BicycleRepository;

#[ORM\Entity(repositoryClass: BicycleRepository::class)]
#[ORM\Table(name: 'bicycle')]
class Bicycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_bike = null;

    public function getId_bike(): ?int
    {
        return $this->id_bike;
    }

    public function setId_bike(int $id_bike): self
    {
        $this->id_bike = $id_bike;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: BicycleStation::class, inversedBy: 'bicycles')]
    #[ORM\JoinColumn(name: 'id_station', referencedColumnName: 'id_station')]
    private ?BicycleStation $bicycleStation = null;

    public function getBicycleStation(): ?BicycleStation
    {
        return $this->bicycleStation;
    }

    public function setBicycleStation(?BicycleStation $bicycleStation): self
    {
        $this->bicycleStation = $bicycleStation;
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

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $battery_level = null;

    public function getBattery_level(): ?float
    {
        return $this->battery_level;
    }

    public function setBattery_level(?float $battery_level): self
    {
        $this->battery_level = $battery_level;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $range_km = null;

    public function getRange_km(): ?float
    {
        return $this->range_km;
    }

    public function setRange_km(?float $range_km): self
    {
        $this->range_km = $range_km;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $last_updated = null;

    public function getLast_updated(): ?\DateTimeInterface
    {
        return $this->last_updated;
    }

    public function setLast_updated(\DateTimeInterface $last_updated): self
    {
        $this->last_updated = $last_updated;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: BicycleRental::class, mappedBy: 'bicycle')]
    private Collection $bicycleRentals;

    public function __construct()
    {
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

    public function getIdBike(): ?int
    {
        return $this->id_bike;
    }

    public function getBatteryLevel(): ?float
    {
        return $this->battery_level;
    }

    public function setBatteryLevel(?float $battery_level): static
    {
        $this->battery_level = $battery_level;

        return $this;
    }

    public function getRangeKm(): ?float
    {
        return $this->range_km;
    }

    public function setRangeKm(?float $range_km): static
    {
        $this->range_km = $range_km;

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->last_updated;
    }

    public function setLastUpdated(\DateTimeInterface $last_updated): static
    {
        $this->last_updated = $last_updated;

        return $this;
    }

}
