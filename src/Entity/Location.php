<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\LocationRepository;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_location = null;

    public function getId_location(): ?int
    {
        return $this->id_location;
    }

    public function setId_location(int $id_location): self
    {
        $this->id_location = $id_location;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $address = null;

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $latitude = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $longitude = null;

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: BicycleStation::class, mappedBy: 'location')]
    private Collection $bicycleStations;

    /**
     * @return Collection<int, BicycleStation>
     */
    public function getBicycleStations(): Collection
    {
        if (!$this->bicycleStations instanceof Collection) {
            $this->bicycleStations = new ArrayCollection();
        }
        return $this->bicycleStations;
    }

    public function addBicycleStation(BicycleStation $bicycleStation): self
    {
        if (!$this->getBicycleStations()->contains($bicycleStation)) {
            $this->getBicycleStations()->add($bicycleStation);
        }
        return $this;
    }

    public function removeBicycleStation(BicycleStation $bicycleStation): self
    {
        $this->getBicycleStations()->removeElement($bicycleStation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Request::class, mappedBy: 'departureLocation')]
    private Collection $departureRequests;

    /**
     * @return Collection<int, Request>
     */
    public function getDepartureRequests(): Collection
    {
        if (!$this->departureRequests instanceof Collection) {
            $this->departureRequests = new ArrayCollection();
        }
        return $this->departureRequests;
    }

    public function addDepartureRequest(Request $request): self
    {
        if (!$this->getDepartureRequests()->contains($request)) {
            $this->getDepartureRequests()->add($request);
        }
        return $this;
    }

    public function removeDepartureRequest(Request $request): self
    {
        $this->getDepartureRequests()->removeElement($request);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Request::class, mappedBy: 'arrivalLocation')]
    private Collection $arrivalRequests;

    /**
     * @return Collection<int, Request>
     */
    public function getArrivalRequests(): Collection
    {
        if (!$this->arrivalRequests instanceof Collection) {
            $this->arrivalRequests = new ArrayCollection();
        }
        return $this->arrivalRequests;
    }

    public function addArrivalRequest(Request $request): self
    {
        if (!$this->getArrivalRequests()->contains($request)) {
            $this->getArrivalRequests()->add($request);
        }
        return $this;
    }

    public function removeArrivalRequest(Request $request): self
    {
        $this->getArrivalRequests()->removeElement($request);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'startLocation')]
    private Collection $startReservations;

    /**
     * @return Collection<int, Reservation>
     */
    public function getStartReservations(): Collection
    {
        if (!$this->startReservations instanceof Collection) {
            $this->startReservations = new ArrayCollection();
        }
        return $this->startReservations;
    }

    public function addStartReservation(Reservation $reservation): self
    {
        if (!$this->getStartReservations()->contains($reservation)) {
            $this->getStartReservations()->add($reservation);
        }
        return $this;
    }

    public function removeStartReservation(Reservation $reservation): self
    {
        $this->getStartReservations()->removeElement($reservation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'endLocation')]
    private Collection $endReservations;

    /**
     * @return Collection<int, Reservation>
     */
    public function getEndReservations(): Collection
    {
        if (!$this->endReservations instanceof Collection) {
            $this->endReservations = new ArrayCollection();
        }
        return $this->endReservations;
    }

    public function addEndReservation(Reservation $reservation): self
    {
        if (!$this->getEndReservations()->contains($reservation)) {
            $this->getEndReservations()->add($reservation);
        }
        return $this;
    }

    public function removeEndReservation(Reservation $reservation): self
    {
        $this->getEndReservations()->removeElement($reservation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'location')]
    private Collection $users;

    public function __construct()
    {
        $this->bicycleStations = new ArrayCollection();
        $this->departureRequests = new ArrayCollection();
        $this->arrivalRequests = new ArrayCollection();
        $this->startReservations = new ArrayCollection();
        $this->endReservations = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        if (!$this->users instanceof Collection) {
            $this->users = new ArrayCollection();
        }
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->getUsers()->removeElement($user);
        return $this;
    }

    public function getIdLocation(): ?int
    {
        return $this->id_location;
    }

    public function __toString(): string
    {
        return $this->address ?? '';
    }
}