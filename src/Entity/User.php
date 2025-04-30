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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_user = null;

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Name cannot be blank', groups: ['Default'])]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Name must be at least {{ limit }} characters long',
        maxMessage: 'Name cannot be longer than {{ limit }} characters',
        groups: ['Default']
    )]
    private ?string $name = null;

    #[Groups(['user:read'])]
    #[ORM\Column(type: 'string', nullable: false, unique: true)]
    #[Assert\NotBlank(message: 'Email cannot be blank')]
    #[Assert\Email(
        message: 'The email {{ value }} is not a valid email address',
        mode: 'strict'
    )]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Email cannot be longer than {{ limit }} characters'
    )]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Password cannot be blank', groups: ['Default'])]
    #[Assert\Length(
        min: 8,
        max: 1000,
        minMessage: 'Password must be at least {{ limit }} characters long',
        groups: ['Default']
    )]
    private ?string $password = null;

    #[Groups(['user:read'])]
    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Phone number cannot be blank')]
    #[Assert\Regex(
        pattern: '/^[2459][0-9]{7}$/',
        message: 'Please enter a valid Tunisian phone number'
    )]
    private ?string $phone_number = null;

    #[ORM\Column(type: 'string', enumType: ROLE::class)]
    #[Assert\NotNull(message: 'Role must be specified', groups: ['Default'])]
    private ROLE $role;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'id_location', referencedColumnName: 'id_location')]
    private ?Location $location = null;

    #[ORM\Column(type: 'string', enumType: GENDER::class)]
    #[Assert\NotNull(message: 'Gender must be specified')]
    private GENDER $gender;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Profile picture URL cannot be longer than {{ limit }} characters')]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_verified = false;

    #[ORM\Column(type: 'string', length: 20, enumType: ACCOUNT_STATUS::class, options: ['default' => 'ACTIVE'])]
    private ACCOUNT_STATUS $account_status = ACCOUNT_STATUS::ACTIVE;

    #[Groups(['user:read'])]
    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotNull(message: 'Date of birth is required')]
    #[Assert\LessThanOrEqual('today', message: 'Date of birth cannot be in the future')]
    #[Assert\GreaterThan('-120 years', message: 'Please enter a valid date of birth')]
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $role = $this->role->value;

        if ($role === 'CLIENT') {
            return ['ROLE_USER'];
        } elseif ($role === 'ADMIN') {
            return ['ROLE_ADMIN'];
        }

        return ['ROLE_USER'];
    }
    
    public function eraseCredentials(): void
    {
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
    }    #[Groups(['user:read'])]
    public function getGender(): GENDER
    {
        return $this->gender;
    }

    /**
     * Get gender as string value
     */
    public function getGenderValue(): string
    {
        return $this->gender->value;
    }

    /**
     * Set the gender - accepts either a GENDER enum or a string
     */
    public function setGender($gender): void
    {
        if ($gender instanceof GENDER) {
            $this->gender = $gender;
        } elseif (is_string($gender)) {
            try {
                $this->gender = GENDER::from($gender);
            } catch (\ValueError $e) {
                // Invalid gender value, don't update
                throw new \InvalidArgumentException("Invalid gender value: $gender. Must be one of: MALE, FEMALE");
            }
        }
    }

    #[Groups(['user:read'])]
    public function getDateOfBirth(): ?string
    {
        return $this->date_of_birth?->format('Y-m-d');
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
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

    public function getAccount_status(): string
    {
        return $this->account_status->value;
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

    public function getStatus(): string
    {
        return $this->status->value;
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

    public function isVerified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $is_verified): static
    {
        $this->is_verified = $is_verified;
        return $this;
    }

    public function getAccountStatus(): string
    {
        return $this->account_status->value;
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

    public function getProfilePicturePath(): ?string
    {
        if (!$this->profilePicture) {
            return null;
        }
        return 'uploads/profile_pictures/' . $this->profilePicture;
    }
}