<?php

namespace App\Controller\front\Reservation;

use App\Entity\Reservation;
use App\Entity\Relocation;
use App\Enum\ReservationStatus;
use App\Form\RelocationFormType;
use App\Repository\ReservationRepository;
use App\Repository\DriverRepository;
use App\Service\ReservationService;
use App\Service\RelocationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;

#[Route('/transporter/reservations')]
class TransporterReservationController extends AbstractController
{
    //private const HARDCODED_DRIVER_ID = 6;

    public function __construct(
        private ReservationService $reservationService,
        private RelocationService $relocationService,
        private DriverRepository $driverRepository,
        private EntityManagerInterface $em,
        private readonly ReservationRepository $reservationRepository,
        private readonly Security $security
    ) {}

    #[Route('/', name: 'app_transporter_reservation_list', methods: ['GET'])]
public function list(Request $request, PaginatorInterface $paginator): Response
{
    $user = $this->getUser();
    $driver = $this->driverRepository->findOneBy(['user' => $user]);
    
    if (!$driver) {
        throw $this->createNotFoundException('Aucun conducteur associé à cet utilisateur');
    }
    
    // Récupération des paramètres
    $keyword = trim($request->query->get('keyword', ''));
    $status = $request->query->get('status');
    $date = $request->query->get('date');

    // Validation du statut
    if ($status && !ReservationStatus::tryFrom($status)) {
        throw $this->createNotFoundException('Invalid status');
    }

    // Construction de la requête en utilisant le repository existant
    $query = $this->reservationRepository->findWithFilters($keyword, $status, $date)
        ->andWhere('a.driver = :driver')
        ->setParameter('driver', $driver);

    $reservations = $paginator->paginate(
        $query->getQuery(),
        $request->query->getInt('page', 1),
        9
    );

    // Gestion réponse AJAX
    if ($request->isXmlHttpRequest()) {
        $html = $this->renderView('front/reservation/transporter/_reservation_list.html.twig', [
            'reservations' => $reservations
        ]);
        return new JsonResponse(['html' => $html]);
    }

    // Réponse normale pour l'affichage initial
    return $this->render('front/reservation/transporter/list.html.twig', [
        'reservations' => $reservations,
        'reservation_statuses' => ReservationStatus::cases()
    ]);
}

    #[Route('/{id}/details', name: 'app_transporter_reservation_details', methods: ['GET'])]
    public function details(Reservation $reservation): JsonResponse
    {
        try {
            $details = $this->reservationService->getReservationDetails($reservation);
            return $this->json($details);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to load reservation details',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/accept', name: 'app_transporter_reservation_accept', methods: ['POST'])]
    public function accept(Request $request, Reservation $reservation, RelocationService $relocationService): JsonResponse
    {
        try {
            // Récupération des données brutes pour debug
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            // Debug logging
            error_log("Received data: " . print_r($data, true));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON format');
            }
    
            // Validation des champs obligatoires
            $requiredFields = ['date', 'cost'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field '$field' is required");
                }
            }
    
            // Validation du coût
            $cost = (float)$data['cost'];
            if ($cost <= 0) {
                throw new \InvalidArgumentException("Cost must be greater than 0");
            }
    
            // Validation de la date
            $date = new \DateTime($data['date']);
            $today = new \DateTime('today');
            if ($date < $today) {
                throw new \InvalidArgumentException("Date cannot be in the past");
            }
    
            // Création de la relocation
            $relocation = $relocationService->createFromReservation(
                $reservation,
                $date,
                $cost
            );
    
            // Mise à jour du statut
            $reservation->setStatus(ReservationStatus::CONFIRMED);
            $this->em->flush();
    
            return $this->json([
                'success' => true,
                'message' => 'Reservation accepted successfully',
                'relocationId' => $relocation->getIdRelocation(),
                'newStatus' => $reservation->getStatus()->value
            ]);
    
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

#[Route('/{id}/refuse', name: 'app_transporter_reservation_refuse', methods: ['POST'])]
public function refuse(Reservation $reservation): JsonResponse
{
    try {
        $reservation->setStatus(ReservationStatus::CANCELLED);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Reservation refused successfully',
            'newStatus' => $reservation->getStatus()->value
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 400);
    }
}

    #[Route('/{id}/create-relocation', name: 'app_transporter_relocation_create', methods: ['GET', 'POST'])]
    public function createRelocation(Request $request, Reservation $reservation): Response
    {
        // Pour les requêtes AJAX (chargement du formulaire)
        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            try {
                $form = $this->createForm(RelocationFormType::class);
                
                return $this->render('front/reservation/transporter/_partials/relocation_form.html.twig', [
                    'form' => $form->createView(),
                    'reservation' => $reservation
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'error' => 'Failed to load form: ' . $e->getMessage()
                ], 500);
            }
        }
        // Pour la soumission du formulaire
        $form = $this->createForm(RelocationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $relocation = $this->relocationService->createFromReservation(
                    $reservation,
                    $form->get('date')->getData(),
                    $form->get('cost')->getData()
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Relocation created successfully',
                    'reservationId' => $reservation->getIdReservation()
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }

        // Si le formulaire n'est pas valide
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[$error->getOrigin()->getName()] = $error->getMessage();
        }

        return $this->json([
            'error' => 'Invalid form data',
            'errors' => $errors
        ], 400);
    }

    #[Route('/search', name: 'app_transporter_reservation_search', methods: ['GET'])]
public function search(Request $request, PaginatorInterface $paginator): Response
{
    $user = $this->getUser();
    $driver = $this->driverRepository->findOneBy(['user' => $user]);
    
    $keyword = trim($request->query->get('keyword', ''));
    $status = $request->query->get('status');
    $date = $request->query->get('date');

    $query = $this->reservationRepository
        ->findWithFilters($keyword, $status, $date)
        ->andWhere('a.driver = :driver')
        ->setParameter('driver', $driver)
        ->getQuery();

    $reservations = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        6
    );

    if ($request->isXmlHttpRequest()) {
        $html = $this->renderView('front/reservation/transporter/_reservation_list.html.twig', [
            'reservations' => $reservations
        ]);
        return new JsonResponse(['html' => $html]);
    }

    return $this->render('front/reservation/transporter/list.html.twig', [
        'reservations' => $reservations,
        'reservation_statuses' => ReservationStatus::cases()
    ]);
}
}