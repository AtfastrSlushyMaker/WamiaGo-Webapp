<?php
namespace App\Service;

use App\Entity\Relocation;
use App\Entity\Reservation;
use App\Repository\RelocationRepository;
use Doctrine\ORM\EntityManagerInterface;

class RelocationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RelocationRepository $relocationRepo
    ) {}

    public function createRelocation(Reservation $reservation, array $data): Relocation
    {
        $relocation = new Relocation();
        $relocation->setReservation($reservation);
        $relocation->setDate(new \DateTime($data['date']));
        $relocation->setCost((float)$data['cost']);
        $relocation->setStatus(true);

        $this->em->persist($relocation);
        $this->em->flush();

        return $relocation;
    }

    public function getRelocationsByDriver($driver): array
    {
        return $this->relocationRepo->findByDriver($driver);
    }

  public function createFromReservation(Reservation $reservation, \DateTimeInterface $date, float $cost): Relocation
    {
        $relocation = new Relocation();
        $relocation->setReservation($reservation);
        $relocation->setDate($date);
        $relocation->setCost($cost);
        $relocation->setStatus(true);

        $this->em->persist($relocation);
        $this->em->flush();

        return $relocation;
    }
}