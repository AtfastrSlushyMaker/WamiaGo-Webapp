<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
class Location
{
    private const EARTH_RADIUS_KM = 6371; // Earth's radius in kilometers
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_location', type: 'integer')]
    private ?int $idLocation = null;

    #[ORM\Column(name: 'latitude', type: 'decimal', precision: 10, scale: 6, nullable: false)]
    #[Assert\NotBlank(message: "Latitude cannot be empty")]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "Latitude must be between {{ min }} and {{ max }}"
    )]
    private string|float|null $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'decimal', precision: 10, scale: 6, nullable: false)]
    #[Assert\NotBlank(message: "Longitude cannot be empty")]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "Longitude must be between {{ min }} and {{ max }}"
    )]
    private string|float|null $longitude = null;

    #[ORM\Column(name: 'address', type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Address cannot be empty")]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: BicycleStation::class)]
    private Collection $bicycleStations;

    #[ORM\OneToMany(mappedBy: 'departureLocation', targetEntity: Request::class)]
    private Collection $departureRequests;

    #[ORM\OneToMany(mappedBy: 'arrivalLocation', targetEntity: Request::class)]
    private Collection $arrivalRequests;

    #[ORM\OneToMany(mappedBy: 'startLocation', targetEntity: Reservation::class)]
    private Collection $startReservations;

    #[ORM\OneToMany(mappedBy: 'endLocation', targetEntity: Reservation::class)]
    private Collection $endReservations;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: User::class)]
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

    public function getIdLocation(): ?int
    {
        return $this->idLocation;
    }

    public function getId_location(): ?int
    {
        return $this->idLocation;
    }

    public function setId_location(int $id_location): self
    {
        $this->idLocation = $id_location;
        return $this;
    }

    public function getLatitude(): string|float|null
    {
        return $this->latitude;
    }

    public function setLatitude(string|float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): string|float|null
    {
        return $this->longitude;
    }    public function setLongitude(string|float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }
    
    public function __toString(): string
    {
        return $this->address ?? 'Unknown location';
    }

    public function getAddress(): ?string
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
        if (!$this->bicycleStations instanceof Collection) {
            $this->bicycleStations = new ArrayCollection();
        }
        return $this->bicycleStations;
    }

    public function addBicycleStation(BicycleStation $bicycleStation): self
    {
        if (!$this->getBicycleStations()->contains($bicycleStation)) {
            $this->getBicycleStations()->add($bicycleStation);
            $bicycleStation->setLocation($this);
        }
        return $this;
    }

    public function removeBicycleStation(BicycleStation $bicycleStation): self
    {
        if ($this->getBicycleStations()->removeElement($bicycleStation)) {
            if ($bicycleStation->getLocation() === $this) {
                $bicycleStation->setLocation(null);
            }
        }
        return $this;
    }

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


    public static function calculateDistance(Location $location1, Location $location2): float
    {
        $lat1 = deg2rad($location1->getLatitude());
        $lon1 = deg2rad($location1->getLongitude());
        $lat2 = deg2rad($location2->getLatitude());
        $lon2 = deg2rad($location2->getLongitude());
    
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;
    
        $a = sin($deltaLat/2) * sin($deltaLat/2) +
             cos($lat1) * cos($lat2) * 
             sin($deltaLon/2) * sin($deltaLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return self::EARTH_RADIUS_KM * $c;
    }
    
    /**
     * Get formatted distance between two locations
     * 
     * @param Location $location1 The first location
     * @param Location $location2 The second location
     * @return string Formatted distance (e.g. "1.2 km" or "800 m")
     */
    public static function getFormattedDistance(Location $location1, Location $location2): string
    {
        $distance = self::calculateDistance($location1, $location2);
        
        if ($distance < 1) {
            // Convert to meters if less than 1 km
            return round($distance * 1000) . ' m';
        }
        
        return round($distance, 2) . ' km';
    } 

}