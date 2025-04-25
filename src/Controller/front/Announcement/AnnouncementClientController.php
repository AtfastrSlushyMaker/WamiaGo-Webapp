<?php

namespace App\Controller\front\Announcement;

use App\Entity\Announcement;
use App\Entity\User;
use App\Enum\Zone;
use App\Service\AnnouncementService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Reservation;
use App\Entity\Location;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\ReservationStatus;

#[Route('/announcements')]
class AnnouncementClientController extends AbstractController
{
    private $entityManager;
    private $announcementService;
    private $validator;
    private $tempUserId = 115; // ID utilisateur temporaire

    public function __construct(
        EntityManagerInterface $entityManager,
        AnnouncementService $announcementService,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->announcementService = $announcementService;
        $this->validator = $validator;
    }

    // Méthode helper pour obtenir l'utilisateur temporaire
    private function getTempUser(): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($this->tempUserId);
    }

    #[Route('/', name: 'app_front_announcements')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getTempUser();
        
        $query = $this->entityManager->createQueryBuilder()
            ->select('a', 'd')
            ->from(Announcement::class, 'a')
            ->join('a.driver', 'd')
            ->where('a.status = :status')
            ->setParameter('status', true)
            ->orderBy('a.date', 'DESC')
            ->getQuery();

        $announcements = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        $zones = Zone::cases();

        return $this->render('front/announcement/index.html.twig', [
            'announcements' => $announcements,
            'zones' => $zones,
            'user' => $user
        ]);
    }

 /*   #[Route('/{id}', name: 'app_front_announcement_details')]
    public function details(int $id): Response
    {
        $announcement = $this->entityManager->getRepository(Announcement::class)->find($id);

        if (!$announcement) {
            throw $this->createNotFoundException('Announcement not found');
        }

        return $this->render('front/announcement/details.html.twig', [
            'announcement' => $announcement
        ]);
    }*/

    /*
     * À implémenter plus tard quand on aura le ReservationService
     */
    #[Route('/{id}/reserve', name: 'app_front_announcement_reserve')]
    public function reserve(int $id): Response
    {
        // À compléter ultérieurement
        return $this->redirectToRoute('app_front_announcements');
    }

   
    #[Route('/{id}/modal', name: 'app_front_announcement_modal')]
    public function announcementModal(int $id): JsonResponse
    {
        $announcement = $this->entityManager->getRepository(Announcement::class)->find($id);
    
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }
    
        $html = $this->renderView('front/announcement/_modal_content.html.twig', [
            'announcement' => $announcement
        ]);
    
        return $this->json([
            'content' => $html,
            'reserveUrl' => $this->generateUrl('app_front_announcement_reserve', ['id' => $id])
        ], 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store'
        ]);
    }

    #[Route('/{id}/create-reservation', name: 'app_front_announcement_create_reservation', methods: ['POST'])]
public function createReservation(Request $request, Announcement $announcement): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    // TEMPORAIRE : Utilisateur statique avec ID 115
    $user = $this->entityManager->getRepository(User::class)->find(115);
    if (!$user) {
        return $this->json([
            'success' => false,
            'message' => 'User with ID 115 not found.'
        ], 404);
    }

    $reservation = new Reservation();
    $reservation->setAnnouncement($announcement);
    $reservation->setUser($user); // Utilisation de l'utilisateur statique
    $reservation->setStatus(ReservationStatus::ON_GOING);

    $reservation->setDescription($data['description'] ?? null);
    $reservation->setDate(new \DateTime($data['date'] ?? 'now'));

    $startLocation = $this->entityManager->getRepository(Location::class)->find($data['startLocation'] ?? null);
    $endLocation = $this->entityManager->getRepository(Location::class)->find($data['endLocation'] ?? null);

    $reservation->setStartLocation($startLocation);
    $reservation->setEndLocation($endLocation);

    $errors = $this->validator->validate($reservation);

    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $field = match($error->getPropertyPath()) {
                'description' => 'description',
                'date' => 'date',
                'startLocation' => 'startLocation',
                'endLocation' => 'endLocation',
                default => $error->getPropertyPath()
            };
            $errorMessages[$field] = $error->getMessage();
        }

        return $this->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errorMessages
        ], 422);
    }

    try {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Reservation created successfully!',
            'reservationId' => $reservation->getIdReservation()
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Error creating reservation: ' . $e->getMessage()
        ], 500);
    }
}

#[Route('/api/locations', name: 'app_api_locations', methods: ['GET'])]
public function getLocations(): JsonResponse
{
    $locations = $this->entityManager->getRepository(Location::class)->findAll();
    $data = [];
    
    foreach ($locations as $location) {
        $data[] = [
            'id' => $location->getIdLocation(),
            'name' => $location->getName(),
            'address' => $location->getAddress()
        ];
    }
    
    return $this->json($data);
}

#[Route('/search', name: 'app_front_announcements_search', methods: ['GET'])]
public function search(Request $request, PaginatorInterface $paginator): Response
{
    $user = $this->getTempUser();
    
    // Récupération et validation des paramètres
    $keyword = trim($request->query->get('keyword', ''));
    $zone = $request->query->get('zone');
    $date = $request->query->get('date');

    // Validation de la date
    $dateObj = null;
    if ($date) {
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            $dateObj = null;
        }
    }

    // Construction de la requête
    $query = $this->announcementService->getFilteredQueryBuilder($keyword, $zone, $dateObj);
    
    // Pagination
    $announcements = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        12
    );

    // Réponse AJAX
    if ($request->isXmlHttpRequest()) {
        $html = $this->renderView('front/announcement/_announcement_list.html.twig', [
            'announcements' => $announcements
        ]);
        return new JsonResponse(['html' => $html]);
    }

    return $this->render('front/announcement/index.html.twig', [
        'announcements' => $announcements,
        'zones' => Zone::cases(),
        'user' => $user,
        'filters' => [
            'keyword' => $keyword,
            'zone' => $zone,
            'date' => $date
        ]
    ]);
}
}