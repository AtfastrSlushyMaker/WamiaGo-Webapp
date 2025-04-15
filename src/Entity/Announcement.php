<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\AnnouncementRepository;
use App\Enum\Zone;

#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Table(name: 'announcement')]
class Announcement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_announcement = null;

    public function getId_announcement(): ?int
    {
        return $this->id_announcement;
    }

    public function setId_announcement(int $id_announcement): self
    {
        $this->id_announcement = $id_announcement;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Driver::class, inversedBy: 'announcements')]
    #[ORM\JoinColumn(name: 'id_transporter', referencedColumnName: 'id_driver')]
    private ?Driver $driver = null;

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank(message: "The announcement title is required")]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: "Title must contain at least {{ limit }} characters",
        maxMessage: "Title cannot exceed {{ limit }} characters"
    )]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[Assert\NotBlank(message: "The announcement content is required")]
    #[Assert\Length(
        min: 8,
        max: 2000,
        minMessage: "Content must contain at least {{ limit }} characters",
        maxMessage: "Content cannot exceed {{ limit }} characters"
    )]
    private ?string $content = null;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    #[Assert\NotBlank(message: "The announcement date is required")]
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

    #[ORM\Column(enumType: Zone::class)]
    #[Assert\NotNull(message: "Please select a service zone")]
    private Zone $zone = Zone::NOT_SPECIFIED;

    public function getZone(): Zone
    {
        return $this->zone ?? Zone::NOT_SPECIFIED;
    }

    public function setZone(Zone $zone): self
    {
        $this->zone = $zone;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[Assert\NotNull(message: "Please indicate whether the announcement is active or not")]
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

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'announcement')]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->date = new \DateTime(); // Set date to current time by default
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

    public function getIdAnnouncement(): ?int
    {
        return $this->id_announcement;
    }
}