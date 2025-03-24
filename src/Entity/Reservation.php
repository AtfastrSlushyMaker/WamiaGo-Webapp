<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationRepository;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reservation = null;

    public function getId_reservation(): ?int
    {
        return $this->id_reservation;
    }

    public function setId_reservation(int $id_reservation): self
    {
        $this->id_reservation = $id_reservation;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_start_location', referencedColumnName: 'id_location')]
    private ?Location $startLocation = null;

    public function getStartLocation(): ?Location
    {
        return $this->startLocation;
    }

    public function setStartLocation(?Location $startLocation): self
    {
        $this->startLocation = $startLocation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_end_location', referencedColumnName: 'id_location')]
    private ?Location $endLocation = null;

    public function getEndLocation(): ?Location
    {
        return $this->endLocation;
    }

    public function setEndLocation(?Location $endLocation): self
    {
        $this->endLocation = $endLocation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Announcement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_announcement', referencedColumnName: 'id_announcement')]
    private ?Announcement $announcement = null;

    public function getAnnouncement(): ?Announcement
    {
        return $this->announcement;
    }

    public function setAnnouncement(?Announcement $announcement): self
    {
        $this->announcement = $announcement;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
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

    #[ORM\OneToMany(targetEntity: Relocation::class, mappedBy: 'reservation')]
    private Collection $relocations;

    public function __construct()
    {
        $this->relocations = new ArrayCollection();
    }

    /**
     * @return Collection<int, Relocation>
     */
    public function getRelocations(): Collection
    {
        if (!$this->relocations instanceof Collection) {
            $this->relocations = new ArrayCollection();
        }
        return $this->relocations;
    }

    public function addRelocation(Relocation $relocation): self
    {
        if (!$this->getRelocations()->contains($relocation)) {
            $this->getRelocations()->add($relocation);
        }
        return $this;
    }

    public function removeRelocation(Relocation $relocation): self
    {
        $this->getRelocations()->removeElement($relocation);
        return $this;
    }

    public function getIdReservation(): ?int
    {
        return $this->id_reservation;
    }
}