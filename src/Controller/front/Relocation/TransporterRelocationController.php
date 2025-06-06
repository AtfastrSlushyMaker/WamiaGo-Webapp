<?php

namespace App\Controller\front\Relocation;

use App\Entity\Relocation;
use App\Entity\Reservation;
use App\Form\RelocationType;
use App\Repository\RelocationRepository;
use App\Repository\DriverRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\ReservationStatus; 
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;


#[Route('/transporter/relocations')]
class TransporterRelocationController extends AbstractController
{
    //private const HARDCODED_DRIVER_ID = 6;
    

    public function __construct(
        private EntityManagerInterface $em,
        private RelocationRepository $relocationRepo,
        private readonly DriverRepository $driverRepository,
        private readonly Security $security
    ) {}

    #[Route('/', name: 'app_transporter_relocation_list', methods: ['GET'])]
public function list(Request $request, PaginatorInterface $paginator, DriverRepository $driverRepo): Response
{
    $user = $this->getUser();
    $driver = $this->driverRepository->findOneBy(['user' => $user]);
    
    $filters = [
        'keyword' => $request->query->get('keyword'),
        'status' => $request->query->get('status'),
        'date' => $request->query->get('date')
    ];

    $query = $this->relocationRepo->createSearchQueryBuilder($filters)
        ->andWhere('a.driver = :driver')
        ->setParameter('driver', $driver)
        ->getQuery();

    $relocations = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        4
    );

    if ($request->isXmlHttpRequest()) {
        return $this->render('front/relocation/transporter/_relocation_list.html.twig', [
            'relocations' => $relocations
        ]);
    }

    return $this->render('front/relocation/transporter/list.html.twig', [
        'relocations' => $relocations
    ]);
}

    #[Route('/create/{id}', name: 'app_relocation_create', methods: ['POST'])]
    public function create(Request $request, Reservation $reservation): JsonResponse
    {
        if (!$this->isCsrfTokenValid('relocation'.$reservation->getIdReservation(), $request->request->get('_token'))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $relocation = new Relocation();
        $relocation->setReservation($reservation);
        $relocation->setDate(new \DateTime($request->request->get('date')));
        $relocation->setCost($request->request->get('cost'));
        $relocation->setStatus(true);

        $reservation->setStatus(ReservationStatus::CONFIRMED);

        $this->em->persist($relocation);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Relocation created successfully',
            'relocationId' => $relocation->getIdRelocation()
        ]);
    }

    #[Route('/{id}/details', name: 'app_relocation_details', methods: ['GET'])]
    public function details(Relocation $relocation): JsonResponse
    {
        return $this->json([
            'reservationTitle' => $relocation->getReservation()->getAnnouncement()->getTitle(),
            'clientName' => $relocation->getReservation()->getUser()->getName(),
            'date' => $relocation->getDate()->format('d M Y, H:i'),
            'cost' => $relocation->getCost(),
            'status' => $relocation->isStatus() ? 'Active' : 'Inactive',
            'startLocation' => $relocation->getReservation()->getStartLocation()->getAddress(),
            'endLocation' => $relocation->getReservation()->getEndLocation()->getAddress()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_relocation_edit', methods: ['GET'])]
    public function edit(Relocation $relocation): JsonResponse
    {
        return $this->json([
            'id' => $relocation->getIdRelocation(),
            'date' => $relocation->getDate()->format('Y-m-d'),
            'cost' => $relocation->getCost(),
            'status' => $relocation->isStatus()
        ]);
    }
    
    

    #[Route('/{id}/update', name: 'app_relocation_update', methods: ['POST'])]
    public function update(Request $request, Relocation $relocation): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid data'], 400);
        }
    
        try {
            // Validation de la date
            if (isset($data['date'])) {
                $date = new \DateTime($data['date']);
                if ($date < new \DateTime('today')) {
                    return $this->json(['error' => 'Date cannot be in the past'], 400);
                }
                $relocation->setDate($date);
            }
    
            // Validation du coût
            if (isset($data['cost'])) {
                $cost = (float)$data['cost'];
                if ($cost <= 0) {
                    return $this->json(['error' => 'Cost must be positive'], 400);
                }
                $relocation->setCost($cost);
            }
    
            if (isset($data['status'])) {
                $relocation->setStatus((bool)$data['status']);
            }
    
            $this->em->flush();
    
            return $this->json([
                'success' => true,
                'message' => 'Relocation updated successfully',
                'relocation' => [
                    'id' => $relocation->getIdRelocation(),
                    'date' => $relocation->getDate()->format('d M Y, H:i'),
                    'cost' => number_format($relocation->getCost(), 2),
                    'status' => $relocation->isStatus()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

#[Route('/{id}/delete', name: 'app_relocation_delete', methods: ['POST'])]
public function delete(Relocation $relocation): JsonResponse
{
    // Supprimer la vérification CSRF
    $this->em->remove($relocation);
    $this->em->flush();

    return $this->json([
        'success' => true,
        'message' => 'Relocation deleted successfully'
    ]);
}



#[Route('/calendar', name: 'app_transporter_relocation_calendar', methods: ['GET'])]
public function calendar(DriverRepository $driverRepo): Response
{
    $user = $this->getUser();
    $driver = $this->driverRepository->findOneBy(['user' => $user]);
    
    if (!$driver) {
        throw $this->createNotFoundException('Driver not found');
    }

    return $this->render('front/relocation/transporter/calendar.html.twig', [
        'driver' => $driver
    ]);
}

#[Route('/api/calendar-events', name: 'app_relocation_calendar_events', methods: ['GET'])]
public function getCalendarEvents(DriverRepository $driverRepo): JsonResponse
{
    $user = $this->getUser();
    $driver = $this->driverRepository->findOneBy(['user' => $user]);
    $relocations = $this->relocationRepo->findByDriver($driver);

    $events = [];
    foreach ($relocations as $relocation) {
        // Récupération de la date de relocation
        $relocationDate = clone $relocation->getDate();
        
        // Récupération de l'heure de réservation
        $reservationDateTime = $relocation->getReservation()->getDate();
        
        // Application de l'heure de réservation à la date de relocation
        $relocationDate->setTime(
            (int)$reservationDateTime->format('H'),
            (int)$reservationDateTime->format('i'),
            (int)$reservationDateTime->format('s')
        );
        
        $events[] = [
            'id' => $relocation->getIdRelocation(),
            'title' => $relocation->getReservation()->getAnnouncement()->getTitle(),
            'start' => $relocationDate->format('Y-m-d\TH:i:s'), // Utilisation de la date de relocation avec l'heure de réservation
            //'end' => $relocationDate->modify('+2 hours')->format('Y-m-d\TH:i:s'),
            'color' => $relocation->isStatus() ? '#3788d8' : '#6c757d',
            'extendedProps' => [
                'cost' => number_format($relocation->getCost(), 2),
                'client' => $relocation->getReservation()->getUser()->getName(),
                'startLocation' => $relocation->getReservation()->getStartLocation()->getAddress(),
                'endLocation' => $relocation->getReservation()->getEndLocation()->getAddress(),
                'status' => $relocation->isStatus(),
                'relocationDate' => $relocationDate->format('d M Y, H:i') // Date de relocation avec l'heure de réservation
            ]
        ];
    }

    return $this->json($events);
}
}