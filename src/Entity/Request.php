<?php

namespace App\Entity;

use App\Enum\REQUEST_STATUS;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\RequestRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: RequestRepository::class)]
#[ORM\Table(name: 'request')]
class Request
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_request = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'requests')]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_user')]
    #[Assert\NotNull(message: 'The user cannot be null.')]
    #[Assert\Valid]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'requests')]
    #[ORM\JoinColumn(name: 'id_departure_location', referencedColumnName: 'id_location')]
    #[Assert\NotNull(message: 'The departure location cannot be null.')]
    #[Assert\Valid]
    private ?Location $departureLocation = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'requests')]
    #[ORM\JoinColumn(name: 'id_arrival_location', referencedColumnName: 'id_location')]
    #[Assert\NotNull(message: 'The arrival location cannot be null.')]
    #[Assert\Valid]
    #[Assert\Expression(
        "this.getDepartureLocation() != this.getArrivalLocation()",
        message: "Departure and arrival locations cannot be the same."
    )]
    private ?Location $arrivalLocation = null;

    #[ORM\Column(enumType: REQUEST_STATUS::class)]
    #[Assert\NotNull(message: 'The status cannot be null.')]
    #[Assert\Choice(
        choices: [REQUEST_STATUS::PENDING, REQUEST_STATUS::ACCEPTED, REQUEST_STATUS::CANCELED],
        message: 'The status must be one of the following: Pending, Accepted, or Canceled.'
    )]
    private REQUEST_STATUS $status;

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: 'The request date cannot be null.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'The request date must be a valid datetime.')]
    #[Assert\LessThanOrEqual(
        'now',
        message: 'The request date cannot be in the future.'
    )]
    private ?\DateTimeInterface $request_date = null;

    #[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'request')]
    #[Assert\Valid]
    private Collection $rides;

    public function __construct()
    {
        $this->rides = new ArrayCollection();
        $this->status = REQUEST_STATUS::PENDING; // Default status
        $this->request_date = new \DateTime(); // Default to current date/time
    }

   
    #[Assert\Callback]
    public function validateLocations(ExecutionContextInterface $context, $payload)
    {
        if ($this->departureLocation !== null && $this->arrivalLocation !== null) {
            if ($this->departureLocation === $this->arrivalLocation) {
                $context->buildViolation('Departure and arrival locations cannot be the same.')
                    ->atPath('arrivalLocation')
                    ->addViolation();
            }
        }
    }

    // Getters and setters
    public function getId_request(): ?int
    {
        return $this->id_request;
    }

    public function setId_request(int $id_request): self
    {
        $this->id_request = $id_request;
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

    public function getDepartureLocation(): ?Location
    {
        return $this->departureLocation;
    }

    public function setDepartureLocation(?Location $departureLocation): self
    {
        $this->departureLocation = $departureLocation;
        return $this;
    }

    public function getArrivalLocation(): ?Location
    {
        return $this->arrivalLocation;
    }

    public function setArrivalLocation(?Location $arrivalLocation): self
    {
        $this->arrivalLocation = $arrivalLocation;
        return $this;
    }

    public function getStatus(): REQUEST_STATUS
    {
        return $this->status;
    }

    public function setStatus(REQUEST_STATUS $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRequest_date(): ?\DateTimeInterface
    {
        return $this->request_date;
    }

    public function setRequest_date(\DateTimeInterface $request_date): self
    {
        $this->request_date = $request_date;
        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRides(): Collection
    {
        return $this->rides;
    }

    public function addRide(Ride $ride): self
    {
        if (!$this->rides->contains($ride)) {
            $this->rides->add($ride);
            $ride->setRequest($this);
        }
        return $this;
    }

    public function removeRide(Ride $ride): self
    {
        if ($this->rides->removeElement($ride)) {
            if ($ride->getRequest() === $this) {
                $ride->setRequest(null);
            }
        }
        return $this;
    }

    public function getIdRequest(): ?int
    {
        return $this->id_request;
    }

    public function getRequestDate(): ?\DateTimeInterface
    {
        return $this->request_date;
    }

    public function setRequestDate(\DateTimeInterface $request_date): static
    {
        $this->request_date = $request_date;
        return $this;
    }
}