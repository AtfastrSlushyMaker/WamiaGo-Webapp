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
use App\Service\EmailService;
use App\Repository\DriverRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/announcements')]
class AnnouncementClientController extends AbstractController
{
    private $entityManager;
    private $announcementService;
    private $validator;
    private $emailService;
    private $driverRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnnouncementService $announcementService,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator,
        EmailService $emailService,
        DriverRepository $driverRepository
    ) {
        $this->entityManager = $entityManager;
        $this->announcementService = $announcementService;
        $this->validator = $validator;
        $this->emailService = $emailService;
        $this->driverRepository = $driverRepository;
    }

    // Méthode helper pour obtenir l'utilisateur temporaire
    private function getTempUser(): ?User
    {
        return $this->entityManager->getRepository(User::class)->find(122);
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
public function createReservation(Request $request, Announcement $announcement, MailerInterface $mailer): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $user = $this->entityManager->getRepository(User::class)->find(122);
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'User with ID 122 not found.'], 404);
    }

    $reservation = new Reservation();
    $reservation->setAnnouncement($announcement);
    $reservation->setUser($user);
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

        return $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errorMessages], 422);
    }

    try {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        // Envoi d'email direct sans service
        $driver = $announcement->getDriver();
        if ($driver && $driver->getUser()->getEmail()) {
            try {
                $email = (new Email())
    ->from('azer.ab.rougui@gmail.com')
    ->to('abrouguiazer1920@gmail.com')
    ->subject('New Reservation: ' . $announcement->getTitle())
    ->html("
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>New Reservation Notification</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        .content {
            padding: 30px;
        }
        h1 {
            color: #0056b3;
            margin-top: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .reservation-details {
            background: #f8f9fa;
            border-left: 4px solid #0056b3;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }
        .detail-item {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: 600;
            color: #0056b3;
            display: inline-block;
            width: 140px;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }
        .button {
            display: inline-block;
            background-color: #0056b3;
            color: white !important;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #003d82;
        }
        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 25px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='header'>
            <h1>New Transportation Reservation</h1>
        </div>
        
        <div class='content'>
            <p class='greeting'>Dear Transporter,</p>
            
            <p>We're pleased to inform you that you've received a new reservation request through WamiaGo. Please find the details below:</p>
            
            <div class='reservation-details'>
                <div class='detail-item'>
                    <span class='detail-label'>Service Type:</span>
                    <span>" . $announcement->getTitle() . "</span>
                </div>
                
                <div class='detail-item'>
                    <span class='detail-label'>Date & Time:</span>
                    <span>" . $reservation->getDate()->format('l, F j, Y \a\t g:i A') . "</span>
                </div>
                
                <div class='detail-item'>
                    <span class='detail-label'>Client Name:</span>
                    <span>" . $user->getName() . "</span>
                </div>
                
                <div class='detail-item'>
                    <span class='detail-label'>Pickup Location:</span>
                    <span>" . $startLocation->getAddress() . "</span>
                </div>
                
                <div class='detail-item'>
                    <span class='detail-label'>Destination:</span>
                    <span>" . $endLocation->getAddress() . "</span>
                </div>
                
                <div class='detail-item'>
                    <span class='detail-label'>Special Instructions:</span>
                    <span>" . ($reservation->getDescription() ?: 'None provided') . "</span>
                </div>
            </div>

            <div style='text-align: center;'>
                <a href='" . $request->getSchemeAndHttpHost() . "/transporter/reservations' class='button'>
                    Manage Reservation
                </a>
            </div>
            
            <div class='divider'></div>
            
            <p>Please confirm this reservation at your earliest convenience. If you have any questions or need to discuss any details with the client, please don't hesitate to contact them through our messaging system.</p>
            
            <p>For any technical assistance, our support team is available at <a href='mailto:support@wamiago.com'>support@wamiago.com</a>.</p>
            
            <p>Best regards,<br>
            <strong>The WamiaGo Team</strong></p>
        </div>
        
        <div class='footer'>
            <p>&copy; " . date('Y') . " WamiaGo Transportation Services. All rights reserved.</p>
            <p>123 Business Avenue, City, Country</p>
        </div>
    </div>
</body>
</html>
");

$mailer->send($email);

                return $this->json([
                    'success' => true,
                    'message' => 'Reservation created and email sent successfully!',
                    'reservationId' => $reservation->getIdReservation()
                ]);

            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Error sending email: ' . $e->getMessage()
                ], 500);
            }
        }

        return $this->json([
            'success' => true,
            'message' => 'Reservation created successfully!',
            'reservationId' => $reservation->getIdReservation()
        ]);

    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => 'Error creating reservation: ' . $e->getMessage()], 500);
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