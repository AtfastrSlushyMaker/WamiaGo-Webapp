<?php

namespace App\Entity;

use App\Enum\ReservationStatus;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reservation = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotBlank(message: "The date is required")]
    #[Assert\GreaterThan("today", message: "The date must be in the future")]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(enumType: ReservationStatus::class)]
    private ?ReservationStatus $status = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Description is required")]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: "Description must be at least {{ limit }} characters",
        maxMessage: "Description cannot be longer than {{ limit }} characters"
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_start_location', referencedColumnName: 'id_location')]
    #[Assert\NotNull(message: "Start location is required")]
    private ?Location $startLocation = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_end_location', referencedColumnName: 'id_location')]
    #[Assert\NotNull(message: "End location is required")]
    #[Assert\Expression(
        "this.getStartLocation() !== this.getEndLocation()",
        message: "Start and end locations must be different"
    )]
    private ?Location $endLocation = null;

    #[ORM\ManyToOne(targetEntity: Announcement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_announcement', referencedColumnName: 'id_announcement')]
    private ?Announcement $announcement = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Relocation::class, mappedBy: 'reservation')]
    private Collection $relocations;

    public function __construct()
    {
        $this->relocations = new ArrayCollection();
    }

    public function getId_reservation(): ?int
    {
        return $this->id_reservation;
    }

    public function setId_reservation(int $id_reservation): self
    {
        $this->id_reservation = $id_reservation;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getStatus(): ?ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStartLocation(): ?Location
    {
        return $this->startLocation;
    }

    public function setStartLocation(?Location $startLocation): self
    {
        $this->startLocation = $startLocation;
        return $this;
    }

    public function getEndLocation(): ?Location
    {
        return $this->endLocation;
    }

    public function setEndLocation(?Location $endLocation): self
    {
        $this->endLocation = $endLocation;
        return $this;
    }

    public function getAnnouncement(): ?Announcement
    {
        return $this->announcement;
    }

    public function setAnnouncement(?Announcement $announcement): self
    {
        $this->announcement = $announcement;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Relocation>
     */
    public function getRelocations(): Collection
    {
        return $this->relocations;
    }

    public function addRelocation(Relocation $relocation): self
    {
        if (!$this->relocations->contains($relocation)) {
            $this->relocations->add($relocation);
        }
        return $this;
    }

    public function removeRelocation(Relocation $relocation): self
    {
        $this->relocations->removeElement($relocation);
        return $this;
    }

    public function getIdReservation(): ?int
    {
        return $this->id_reservation;
    }
}
