<?php

namespace App\Controller\front\Announcement;
use App\Entity\Announcement;
use App\Entity\User;
use App\Enum\Zone;
use App\Service\AnnouncementService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Reservation;
use App\Entity\Location;
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
    public function index(): Response
    {
        // Utilisation de l'utilisateur temporaire
        $user = $this->getTempUser();
        
        $announcements = $this->announcementService->getActiveAnnouncements();
        $zones = Zone::cases();

        return $this->render('front/announcement/index.html.twig', [
            'announcements' => $announcements,
            'zones' => $zones,
            'user' => $user
        ]);
    }

    #[Route('/zone/{zone}', name: 'app_front_announcements_by_zone')]
    public function byZone(Zone $zone): Response
    {
        $user = $this->getTempUser();
        $announcements = $this->announcementService->getAnnouncementsByZone($zone);
        $zones = Zone::cases();

        return $this->render('front/announcement/index.html.twig', [
            'announcements' => $announcements,
            'zones' => $zones,
            'selectedZone' => $zone,
            'user' => $user
        ]);
    }

    #[Route('/search', name: 'app_front_announcements_search')]
    public function search(Request $request): Response
    {
        $user = $this->getTempUser();
        $keyword = $request->query->get('keyword');
        $announcements = $this->announcementService->searchAnnouncements($keyword);
        $zones = Zone::cases();

        return $this->render('front/announcement/index.html.twig', [
            'announcements' => $announcements,
            'zones' => $zones,
            'searchKeyword' => $keyword,
            'user' => $user
        ]);
    }

    #[Route('/{id}', name: 'app_front_announcement_details')]
    public function details(int $id): Response
    {
        $user = $this->getTempUser();
        $announcement = $this->entityManager->getRepository(Announcement::class)->find($id);

        if (!$announcement) {
            throw $this->createNotFoundException('Announcement not found');
        }

        return $this->render('front/announcement/details.html.twig', [
            'announcement' => $announcement,
            'user' => $user
        ]);
    }

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
        $user = $this->getTempUser();
        $announcement = $this->entityManager->getRepository(Announcement::class)->find($id);
    
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }
    
        $html = $this->renderView('front/announcement/_modal_content.html.twig', [
            'announcement' => $announcement,
            'user' => $user
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
        
        // Create new reservation
        $reservation = new Reservation();
        $reservation->setAnnouncement($announcement);
        $reservation->setUser($this->getTempUser()); // Utilisateur temporaire au lieu de getUser()
        $reservation->setStatus(ReservationStatus::ON_GOING);
        
        // Manually bind data
        $reservation->setDescription($data['description'] ?? null);
        $reservation->setDate(new \DateTime($data['date'] ?? 'now'));
        
        // Set locations
        $startLocation = $this->entityManager->getRepository(Location::class)->find($data['startLocation'] ?? null);
        $endLocation = $this->entityManager->getRepository(Location::class)->find($data['endLocation'] ?? null);
        
        $reservation->setStartLocation($startLocation);
        $reservation->setEndLocation($endLocation);
        
        // Validate
        $errors = $this->validator->validate($reservation);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                // Mappez les noms des propriétés aux IDs des champs du formulaire
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
}