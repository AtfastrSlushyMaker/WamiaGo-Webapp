<?php

namespace App\Service;

use App\Entity\Announcement;
use App\Entity\Driver;
use App\Enum\Zone;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnnouncementService
{
    private $entityManager;
    private $announcementRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnnouncementRepository $announcementRepository
    ) {
        $this->entityManager = $entityManager;
        $this->announcementRepository = $announcementRepository;
    }

    // CRUD de base
    public function createAnnouncement(
        Driver $driver,
        string $title,
        string $content,
        Zone $zone,
        \DateTimeInterface $date,
        bool $status = true
    ): Announcement {
        $announcement = new Announcement();
        $announcement->setDriver($driver);
        $announcement->setTitle($title);
        $announcement->setContent($content);
        $announcement->setZone($zone);
        $announcement->setDate($date);
        $announcement->setStatus($status);

        $this->entityManager->persist($announcement);
        $this->entityManager->flush();

        return $announcement;
    }

    public function updateAnnouncement(Announcement $announcement): Announcement
    {
        $this->entityManager->flush();
        return $announcement;
    }

    public function deleteAnnouncement(Announcement $announcement): void
    {
        $this->entityManager->remove($announcement);
        $this->entityManager->flush();
    }

    public function toggleAnnouncementStatus(Announcement $announcement): void
    {
        $announcement->setStatus(!$announcement->isStatus());
        $this->entityManager->flush();
    }

    public function getActiveAnnouncements(): array
    {
        return $this->announcementRepository->findActiveAnnouncements();
    }

    public function getAnnouncementsByZone(Zone $zone): array
    {
        return $this->announcementRepository->findByZone($zone);
    }

    public function getAnnouncementsByDriver(Driver $driver): array
    {
        return $this->announcementRepository->findByDriverId($driver->getIdDriver());
    }

    public function searchAnnouncements(string $keyword): array
    {
        return $this->announcementRepository->findByKeyword($keyword);
    }

    public function filterAnnouncements(array $filters): array
    {
        return $this->announcementRepository->findByFilters($filters);
    }

   
    public function canBeReserved(Announcement $announcement): bool
    {
        return $announcement->isStatus() 
            && $announcement->getDate() >= new \DateTime('now');
    }
}