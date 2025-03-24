<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\RelocationRepository;

#[ORM\Entity(repositoryClass: RelocationRepository::class)]
#[ORM\Table(name: 'relocation')]
class Relocation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_relocation = null;

    public function getId_relocation(): ?int
    {
        return $this->id_relocation;
    }

    public function setId_relocation(int $id_relocation): self
    {
        $this->id_relocation = $id_relocation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'relocations')]
    #[ORM\JoinColumn(name: 'id_reservation', referencedColumnName: 'id_reservation')]
    private ?Reservation $reservation = null;

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;
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

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $cost = null;

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function getIdRelocation(): ?int
    {
        return $this->id_relocation;
    }

}
