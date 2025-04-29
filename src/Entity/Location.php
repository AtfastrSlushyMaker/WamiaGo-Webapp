<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity()]
#[ORM\Table(name: 'location')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_location', type: 'integer')]
    private ?int $idLocation = null;

    #[ORM\Column(name: 'latitude', type: 'decimal', precision: 10, scale: 6)]
    #[Assert\NotBlank(message: "Latitude cannot be empty")]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "Latitude must be between {{ min }} and {{ max }}"
    )]
    private string $latitude;

    #[ORM\Column(name: 'longitude', type: 'decimal', precision: 10, scale: 6)]
    #[Assert\NotBlank(message: "Longitude cannot be empty")]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "Longitude must be between {{ min }} and {{ max }}"
    )]
    private string $longitude;

    #[ORM\Column(name: 'address', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Address cannot be empty")]
    private string $address;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: BicycleStation::class)]
    private Collection $bicycleStations;

    public function __construct()
    {
        $this->bicycleStations = new ArrayCollection();
    }

    public function getIdLocation(): ?int
    {
        return $this->idLocation;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return Collection<int, BicycleStation>
     */
    public function getBicycleStations(): Collection
    {
        return $this->bicycleStations;
    }

    public function addBicycleStation(BicycleStation $bicycleStation): self
    {
        if (!$this->bicycleStations->contains($bicycleStation)) {
            $this->bicycleStations->add($bicycleStation);
            $bicycleStation->setLocation($this);
        }

        return $this;
    }

    public function removeBicycleStation(BicycleStation $bicycleStation): self
    {
        if ($this->bicycleStations->removeElement($bicycleStation)) {
            // Set the owning side to null if necessary
            if ($bicycleStation->getLocation() === $this) {
                $bicycleStation->setLocation(null);
            }
        }

        return $this;
    }
}