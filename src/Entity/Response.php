<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\ResponseRepository;

#[ORM\Entity(repositoryClass: ResponseRepository::class)]
#[ORM\Table(name: 'response')]
class Response
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_response = null;

    public function getId_response(): ?int
    {
        return $this->id_response;
    }

    public function setId_response(int $id_response): self
    {
        $this->id_response = $id_response;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'responses')]
    #[ORM\JoinColumn(name: 'id_reclamation', referencedColumnName: 'id_reclamation')]
    #[Assert\NotNull(message: 'Please select a reclamation')]
    private ?Reclamation $reclamation = null;

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'The response content cannot be empty')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'The response content should be at least {{ limit }} characters',
        maxMessage: 'The response content cannot be longer than {{ limit }} characters'
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

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: 'Please provide a date')]
    #[Assert\Type("\DateTimeInterface", message: 'Please provide a valid date')]
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

    public function getIdResponse(): ?int
    {
        return $this->id_response;
    }

}
