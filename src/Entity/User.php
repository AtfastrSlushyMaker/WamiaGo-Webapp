<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\STATUS;
use App\Enum\ROLE;
use App\Enum\GENDER;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_user = null;

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    private ?string $name = null;

    #[Groups(['user:read'])]
    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $password = null;

    #[Groups(['user:read'])]
    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $phone_number = null;

    #[ORM\Column(type: 'string', enumType: ROLE::class)]
    private ROLE $role;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'id_location', referencedColumnName: 'id_location')]
    private ?Location $location = null;

    #[ORM\Column(type: 'string', enumType: GENDER::class)]
    private GENDER $gender;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profile_picture = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_verified = false;

    #[ORM\Column(type: 'string', length: 20, enumType: ACCOUNT_STATUS::class, options: ['default' => 'ACTIVE'])]
    private ACCOUNT_STATUS $account_status = ACCOUNT_STATUS::ACTIVE;

    #[Groups(['user:read'])]
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_of_birth = null;

    #[ORM\Column(type: 'string', length: 20, enumType: \App\Enum\STATUS::class, options: ['default' => 'OFFLINE'])]
    private STATUS $status = STATUS::OFFLINE;

    #[ORM\OneToMany(targetEntity: BicycleRental::class, mappedBy: 'user')]
    private Collection $bicycleRentals;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'user')]
    private Collection $bookings;

    #[ORM\OneToMany(targetEntity: Driver::class, mappedBy: 'user')]
    private Collection $drivers;

    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'user')]
    private Collection $ratings;

    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'user')]
    private Collection $reclamations;

    #[ORM\OneToMany(targetEntity: Request::class, mappedBy: 'user')]
    private Collection $requests;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user')]
    private Collection $reservations;

    public function __construct()
    {
        $this->bicycleRentals = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->drivers = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->requests = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    // UserInterface methods
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $role = $this->role->value; // Convert ROLE enum to string

        // Debug
        error_log('User role: ' . $role);

        // Keep your existing code
        if (!str_starts_with($role, 'ROLE_')) {
            if ($role === 'CLIENT') {
                return ['ROLE_USER'];
            } elseif ($role === 'ADMIN') {
                return ['ROLE_ADMIN'];
            } else {
                return ['ROLE_' . strtoupper($role)];
            }
        }

        return [$role];
    }
    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    // Original getters and setters
    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPhone_number(): ?string
    {
        return $this->phone_number;
    }

    public function setPhone_number(string $phone_number): self
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    // Ensure the ROLE enum is converted to a string value in the getRole method
    public function getRole(): string
    {
        return $this->role->value;
    }

    public function setRole(ROLE $role): void
    {
        $this->role = $role;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    #[Groups(['user:read'])]
    public function getGender(): GENDER
    {
        return $this->gender;
    }

    public function setGender(GENDER $gender): void
    {
        $this->gender = $gender;
    }

    #[Groups(['user:read'])]
    public function getDateOfBirth(): ?string
    {
        return $this->date_of_birth?->format('Y-m-d');
    }

    public function getProfile_picture(): ?string
    {
        return $this->profile_picture;
    }

    public function setProfile_picture(?string $profile_picture): self
    {
        $this->profile_picture = $profile_picture;
        return $this;
    }

    public function is_verified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIs_verified(bool $is_verified): self
    {
        $this->is_verified = $is_verified;
        return $this;
    }

    public function getAccount_status(): ACCOUNT_STATUS
    {
        return $this->account_status;
    }

    public function setAccount_status(ACCOUNT_STATUS $account_status): void
    {
        $this->account_status = $account_status;
    }

    public function getDate_of_birth(): ?\DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function setDate_of_birth(?\DateTimeInterface $date_of_birth): self
    {
        $this->date_of_birth = $date_of_birth;
        return $this;
    }

    public function getStatus(): STATUS
    {
        return $this->status;
    }


    public function setStatus(STATUS $status): self
    {
        error_log('setStatus called with value: ' . print_r($status, true));

        if (!$status instanceof \App\Enum\STATUS) {
            throw new \InvalidArgumentException('Invalid value passed to setStatus. Expected an instance of STATUS enum.');
        }

        $this->status = $status;
        return $this;
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

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        if (!$this->bookings instanceof Collection) {
            $this->bookings = new ArrayCollection();
        }
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->getBookings()->contains($booking)) {
            $this->getBookings()->add($booking);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        $this->getBookings()->removeElement($booking);
        return $this;
    }

    /**
     * @return Collection<int, Driver>
     */
    public function getDrivers(): Collection
    {
        if (!$this->drivers instanceof Collection) {
            $this->drivers = new ArrayCollection();
        }
        return $this->drivers;
    }

    public function addDriver(Driver $driver): self
    {
        if (!$this->getDrivers()->contains($driver)) {
            $this->getDrivers()->add($driver);
        }
        return $this;
    }

    public function removeDriver(Driver $driver): self
    {
        $this->getDrivers()->removeElement($driver);
        return $this;
    }

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

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        if (!$this->reclamations instanceof Collection) {
            $this->reclamations = new ArrayCollection();
        }
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): self
    {
        if (!$this->getReclamations()->contains($reclamation)) {
            $this->getReclamations()->add($reclamation);
        }
        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): self
    {
        $this->getReclamations()->removeElement($reclamation);
        return $this;
    }

    /**
     * @return Collection<int, Request>
     */
    public function getRequests(): Collection
    {
        if (!$this->requests instanceof Collection) {
            $this->requests = new ArrayCollection();
        }
        return $this->requests;
    }

    public function addRequest(Request $request): self
    {
        if (!$this->getRequests()->contains($request)) {
            $this->getRequests()->add($request);
        }
        return $this;
    }

    public function removeRequest(Request $request): self
    {
        $this->getRequests()->removeElement($request);
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        if (!$this->reservations instanceof Collection) {
            $this->reservations = new ArrayCollection();
        }
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->getReservations()->contains($reservation)) {
            $this->getReservations()->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->getReservations()->removeElement($reservation);
        return $this;
    }

    // PSR-compliant getters and setters for Symfony Form component
    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): static
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profile_picture;
    }

    public function setProfilePicture(?string $profile_picture): static
    {
        $this->profile_picture = $profile_picture;
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $is_verified): static
    {
        $this->is_verified = $is_verified;
        return $this;
    }

    public function getAccountStatus(): ACCOUNT_STATUS
    {
        return $this->account_status;
    }

    public function setAccountStatus(ACCOUNT_STATUS $account_status): void
    {
        $this->account_status = $account_status;
    }


    public function setDateOfBirth(?\DateTimeInterface $date_of_birth): static
    {
        $this->date_of_birth = $date_of_birth;
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }
}