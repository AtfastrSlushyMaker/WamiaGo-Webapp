<?php

namespace App\Controller\front\Announcement;
use App\Entity\Announcement;
use App\Entity\User;
use App\Enum\Zone;
use App\Service\AnnouncementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/announcements')]
class AnnouncementClientController extends AbstractController
{
    private $entityManager;
    private $announcementService;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnnouncementService $announcementService
    ) {
        $this->entityManager = $entityManager;
        $this->announcementService = $announcementService;
    }

    #[Route('/', name: 'app_front_announcements')]
    public function index(): Response
    {
        // Utilisation d'un utilisateur statique (ID 1) temporairement
        $user = $this->entityManager->getRepository(User::class)->find(1);
        
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
        $announcements = $this->announcementService->getAnnouncementsByZone($zone);
        $zones = Zone::cases();

        return $this->render('front/announcement/index.html.twig', [
            'announcements' => $announcements,
            'zones' => $zones,
            'selectedZone' => $zone
        ]);
    }

    #[Route('/search', name: 'app_front_announcements_search')]
    public function search(Request $request): Response
    {
        $keyword = $request->query->get('keyword');
        $announcements = $this->announcementService->searchAnnouncements($keyword);
        $zones = Zone::cases();

        return $this->render('front/announcement/index.html.twig', [
            'announcements' => $announcements,
            'zones' => $zones,
            'searchKeyword' => $keyword
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
}