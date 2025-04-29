<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DriverRepository;

#[ORM\Entity(repositoryClass: DriverRepository::class)]
#[ORM\Table(name: 'driver')]
class Driver
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_driver = null;

    public function getId_driver(): ?int
    {
        return $this->id_driver;
    }

    public function setId_driver(int $id_driver): self
    {
        $this->id_driver = $id_driver;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'drivers')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $permit_number = null;

    public function getPermit_number(): ?string
    {
        return $this->permit_number;
    }

    public function setPermit_number(string $permit_number): self
    {
        $this->permit_number = $permit_number;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $status = null;

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Announcement::class, mappedBy: 'driver')]
    private Collection $announcements;

    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncements(): Collection
    {
        if (!$this->announcements instanceof Collection) {
            $this->announcements = new ArrayCollection();
        }
        return $this->announcements;
    }

    public function addAnnouncement(Announcement $announcement): self
    {
        if (!$this->getAnnouncements()->contains($announcement)) {
            $this->getAnnouncements()->add($announcement);
        }
        return $this;
    }

    public function removeAnnouncement(Announcement $announcement): self
    {
        $this->getAnnouncements()->removeElement($announcement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'driver')]
    private Collection $ratings;

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        if (!$this->ratings instanceof Collection) {
            $this->ratings = new ArrayCollection();
        }
        return $this->ratings;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->getRatings()->contains($rating)) {
            $this->getRatings()->add($rating);
        }
        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        $this->getRatings()->removeElement($rating);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'driver')]
    private Collection $rides;

    /**
     * @return Collection<int, Ride>
     */
    public function getRides(): Collection
    {
        if (!$this->rides instanceof Collection) {
            $this->rides = new ArrayCollection();
        }
        return $this->rides;
    }

    public function addRide(Ride $ride): self
    {
        if (!$this->getRides()->contains($ride)) {
            $this->getRides()->add($ride);
        }
        return $this;
    }

    public function removeRide(Ride $ride): self
    {
        $this->getRides()->removeElement($ride);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'driver')]
    private Collection $trips;

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

    #[ORM\OneToMany(targetEntity: Vehicle::class, mappedBy: 'driver')]
    private Collection $vehicles;

    public function __construct()
    {
        $this->announcements = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->rides = new ArrayCollection();
        $this->trips = new ArrayCollection();
        $this->vehicles = new ArrayCollection();
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function getVehicles(): Collection
    {
        if (!$this->vehicles instanceof Collection) {
            $this->vehicles = new ArrayCollection();
        }
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->getVehicles()->contains($vehicle)) {
            $this->getVehicles()->add($vehicle);
        }
        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        $this->getVehicles()->removeElement($vehicle);
        return $this;
    }

    public function getIdDriver(): ?int
    {
        return $this->id_driver;
    }

    public function getPermitNumber(): ?string
    {
        return $this->permit_number;
    }

    public function setPermitNumber(string $permit_number): static
    {
        $this->permit_number = $permit_number;

        return $this;
    }


}
