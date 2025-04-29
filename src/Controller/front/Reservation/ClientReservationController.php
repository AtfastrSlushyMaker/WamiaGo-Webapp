<?php

namespace App\Controller\front\Reservation;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\ReservationService;
use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


#[Route('/client/reservations')]
class ClientReservationController extends AbstractController
{
    private const HARDCODED_CLIENT_ID = 122;

    public function __construct(
        private ReservationService $reservationService,
        private EntityManagerInterface $em,
        private readonly ReservationRepository $reservationRepository
    ) {}

    #[Route('/', name: 'app_client_reservation_list', methods: ['GET'])]
public function list(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
{
    $client = $userRepository->find(self::HARDCODED_CLIENT_ID);
    
    // Récupération et validation des paramètres
    $keyword = trim($request->query->get('keyword', ''));
    $status = $request->query->get('status');
    $date = $request->query->get('date');

    // Validation du statut
    if ($status && !ReservationStatus::tryFrom($status)) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Invalid status value'], 400);
        }
        throw $this->createNotFoundException('Invalid status');
    }

    // Validation de la date
if ($request->query->has('date')) {
    $date = $request->query->get('date');
    try {
        $dateObj = new \DateTime($date);
        $dateParam = $dateObj->format('Y-m-d');
    } catch (\Exception $e) {
        
    }
}

// Appel au repository
$query = $this->reservationRepository->findWithFilters_client(
    $keyword,
    $status,
    $dateParam ?? null
);

   

    try {
        $reservations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );
    } catch (\Exception $e) {
        // Gestion d'erreur améliorée
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Invalid pagination request'], 400);
        }
        throw $this->createNotFoundException('Page not found');
    }

    // Réponse AJAX
    if ($request->isXmlHttpRequest()) {
        return $this->render('front/reservation/client/_reservation_list.html.twig', [
            'reservations' => $reservations
        ]);
    }

    return $this->render('front/reservation/client/list.html.twig', [
        'reservations' => $reservations
    ]);
}

    #[Route('/{id}/details', name: 'app_client_reservation_details', methods: ['GET'])]
    public function details(Reservation $reservation): JsonResponse
    {
        return $this->json($this->reservationService->getClientReservationDetails($reservation));
    }

    #[Route('/{id}/update', name: 'app_client_reservation_update', methods: ['POST'])]
    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validation simplifiée sans CSRF
            if (empty($data)) {
                throw new \Exception('Invalid request data');
            }
    
            // Mise à jour de la réservation
            $reservation->setDescription($data['description'] ?? '');
            $reservation->setDate(new \DateTime($data['date']));
            
            $startLocation = $this->em->getRepository(Location::class)->find($data['startLocation'] ?? null);
            $endLocation = $this->em->getRepository(Location::class)->find($data['endLocation'] ?? null);
            
            if (!$startLocation || !$endLocation) {
                throw new \Exception('Invalid location selected');
            }
    
            $reservation->setStartLocation($startLocation);
            $reservation->setEndLocation($endLocation);
    
            $this->em->flush();
    
            return $this->json([
                'success' => true,
                'message' => 'Reservation updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/delete', name: 'app_client_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$this->isCsrfTokenValid('reservation', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        try {
            $this->em->remove($reservation);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Reservation deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/update-form', name: 'app_client_reservation_update_form', methods: ['GET'])]
public function getUpdateForm(Reservation $reservation, UserRepository $userRepository): JsonResponse
{
    try {
        // Vérification basique des données
        if (!$reservation->getStartLocation() || !$reservation->getEndLocation()) {
            throw new \Exception('Missing location data');
        }

        return $this->json([
            'success' => true,
            'html' => $this->renderView('front/reservation/client/_partials/update_form.html.twig', [
                'reservation' => $reservation,
                'locations' => $this->em->getRepository(Location::class)->findAll()
            ])
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
}