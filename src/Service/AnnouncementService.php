<?php

namespace App\Service;

use App\Entity\Announcement;
use App\Entity\Driver;
use App\Enum\Zone;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

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
        return $this->announcementRepository->findByZone($zone->value);
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

    public function getAnnouncementsForDatatable(int $start, int $length, ?string $search, ?array $order): array
{
    $qb = $this->entityManager->createQueryBuilder()
        ->select('a', 'd')
        ->from(Announcement::class, 'a')
        ->join('a.driver', 'd');

    if ($search) {
        $qb->where('a.title LIKE :search OR a.content LIKE :search OR d.firstName LIKE :search OR d.lastName LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($order) {
        $column = $order['column'] ?? 0;
        $dir = $order['dir'] ?? 'asc';
        
        $columns = ['a.id', 'a.title', 'a.content', 'a.zone', 'a.date', 'a.status', 'd.firstName'];
        $orderBy = $columns[$column] ?? $columns[0];
        
        $qb->orderBy($orderBy, $dir);
    }

    $qb->setFirstResult($start)
       ->setMaxResults($length);

    $results = $qb->getQuery()->getResult();

    $data = [];
    foreach ($results as $announcement) {
        $data[] = [
            'id' => $announcement->getIdAnnouncement(),
            'title' => $announcement->getTitle(),
            'content' => $announcement->getContent(),
            'zone' => $announcement->getZone()->value,
            'date' => $announcement->getDate()->format('Y-m-d H:i:s'),
            'status' => $announcement->isStatus(),
            'driver' => [
                'firstName' => $announcement->getDriver()->getFirstName(),
                'lastName' => $announcement->getDriver()->getLastName(),
            ],
        ];
    }

    return $data;
}

public function countAllAnnouncements(): int
{
    return $this->entityManager->createQueryBuilder()
        ->select('COUNT(a.id)')
        ->from(Announcement::class, 'a')
        ->getQuery()
        ->getSingleScalarResult();
}

public function countFilteredAnnouncements(?string $search): int
{
    $qb = $this->entityManager->createQueryBuilder()
        ->select('COUNT(a.id)')
        ->from(Announcement::class, 'a')
        ->join('a.driver', 'd');

    if ($search) {
        $qb->where('a.title LIKE :search OR a.content LIKE :search OR d.firstName LIKE :search OR d.lastName LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    return $qb->getQuery()->getSingleScalarResult();
}

public function getAnnouncementsQueryByDriver(Driver $driver): QueryBuilder
{
    return $this->announcementRepository->getQueryByDriver($this->entityManager->getRepository(Driver::class)->find($driver->getIdDriver()));
}

public function getAnnouncementDetails(int $id): ?Announcement
{
    return $this->announcementRepository->findWithDetails($id);
}

public function getFeaturedAnnouncements(int $limit = 3): array
{
    return $this->announcementRepository->createQueryBuilder('a')
        ->where('a.status = true')
        ->orderBy('a.date', 'DESC') 
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

public function getFilteredQuery(array $filters): Query
{
    $qb = $this->entityManager->createQueryBuilder()
        ->select('a', 'd')
        ->from(Announcement::class, 'a')
        ->join('a.driver', 'd')
        ->where('a.status = :status')
        ->setParameter('status', true);

    if (!empty($filters['keyword'])) {
        $qb->andWhere('a.title LIKE :keyword OR a.content LIKE :keyword')
           ->setParameter('keyword', '%'.$filters['keyword'].'%');
    }

    if (!empty($filters['zone'])) {
        $qb->andWhere('a.zone = :zone')
           ->setParameter('zone', Zone::tryFrom($filters['zone']));
    }

    if (!empty($filters['date'])) {
        $qb->andWhere('DATE(a.date) = :date')
           ->setParameter('date', $filters['date']);
    }

    return $qb->orderBy('a.date', 'DESC')
              ->getQuery();
}

public function getFilteredQueryBuilder(?string $keyword = null, ?string $zone = null, ?\DateTime $date = null): QueryBuilder
{
    $qb = $this->entityManager->createQueryBuilder()
        ->select('a', 'd')
        ->from(Announcement::class, 'a')
        ->join('a.driver', 'd')
        ->where('a.status = :status')
        ->setParameter('status', true);

    // Filtre par mot-clé
    if (!empty($keyword)) {
        $qb->andWhere('(a.title LIKE :keyword OR a.content LIKE :keyword)')
           ->setParameter('keyword', '%'.$keyword.'%');
    }

    // Filtre par zone
    if (!empty($zone)) {
        $qb->andWhere('a.zone = :zone')
           ->setParameter('zone', $zone);
    }

    // Filtre par date
    if ($date instanceof \DateTime) {
        $startDate = clone $date;
        $startDate->setTime(0, 0, 0);
        
        $endDate = clone $startDate;
        $endDate->modify('+1 day');
        
        $qb->andWhere('a.date >= :startDate AND a.date < :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);
    }

    return $qb->orderBy('a.date', 'DESC');
}

public function searchAnnouncementsByDriver(Driver $driver, string $searchQuery): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        return $queryBuilder
            ->select('a')
            ->from(Announcement::class, 'a')
            ->where('a.driver = :driver')
            ->andWhere('a.title LIKE :searchQuery OR a.content LIKE :searchQuery')
            ->setParameter('driver', $driver)
            ->setParameter('searchQuery', '%' . $searchQuery . '%')
            ->getQuery();
    }
}