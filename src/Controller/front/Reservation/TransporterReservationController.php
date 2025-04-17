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

#[Route('/transporter/reservations')]
class TransporterReservationController extends AbstractController
{
    private const HARDCODED_DRIVER_ID = 6;

    public function __construct(
        private ReservationService $reservationService,
        private RelocationService $relocationService,
        private DriverRepository $driverRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('/', name: 'app_transporter_reservation_list', methods: ['GET'])]
    public function list(): Response
    {
        $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
        
        if (!$driver) {
            throw $this->createNotFoundException('Driver not found');
        }

        $reservations = $this->reservationService->getReservationsByDriver($driver);

        return $this->render('front/reservation/transporter/list.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/{id}/details', name: 'app_transporter_reservation_details', methods: ['GET'])]
    public function details(Reservation $reservation): JsonResponse
    {
        return $this->json($this->reservationService->getReservationDetails($reservation));
    }

    #[Route('/{id}/accept', name: 'app_transporter_reservation_accept', methods: ['POST'])]
    #[Route('/{id}/accept', name: 'app_transporter_reservation_accept', methods: ['POST'])]
public function accept(Request $request, Reservation $reservation, RelocationService $relocationService): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    if (!$this->isCsrfTokenValid('reservation', $data['_token'] ?? '')) {
        return $this->json(['error' => 'Invalid CSRF token'], 403);
    }

    try {
        // Validate input
        if (empty($data['date']) || empty($data['cost'])) {
            throw new \InvalidArgumentException('Date and cost are required');
        }

        if ((float)$data['cost'] <= 0) {
            throw new \InvalidArgumentException('Cost must be greater than 0');
        }

        $relocationDate = new \DateTime($data['date']);
        if ($relocationDate < new \DateTime('today')) {
            throw new \InvalidArgumentException('Date cannot be in the past');
        }

        // Create relocation
        $relocation = $relocationService->createFromReservation(
            $reservation,
            $relocationDate,
            (float)$data['cost']
        );

        // Update reservation status
        $this->em->flush(); // Persist changes to the database
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Reservation accepted and relocation created',
            'newStatus' => $reservation->getStatus()->value
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 400);
    }
}

#[Route('/{id}/refuse', name: 'app_transporter_reservation_refuse', methods: ['POST'])]
public function refuse(Request $request, Reservation $reservation): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    if (!$this->isCsrfTokenValid('reservation', $data['_token'] ?? '')) {
        return $this->json(['error' => 'Invalid CSRF token'], 403);
    }

    try {
        $reservation->setStatus(ReservationStatus::CANCELLED);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Reservation refused successfully',
            'newStatus' => $reservation->getStatus()->value
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 400);
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
}