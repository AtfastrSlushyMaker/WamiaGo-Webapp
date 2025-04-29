<?php

namespace App\Controller\front\Announcement;

use App\Entity\Announcement;
use App\Entity\Driver;
use App\Form\TransporterAnnouncementType;
use App\Repository\DriverRepository;
use App\Service\AnnouncementService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\AnnouncementRepository;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\OpenAIService;
use App\Utils\BadWordFilter; // Adjust the namespace to the actual location of BadWordFilter
use App\Enum\Zone; // Adjust 'App\Enum\Zone' to the correct namespace of the Zone class or enum

use function PHPUnit\Framework\throwException;

#[Route('/transporter/announcements')]
class TransporterAnnouncementController extends AbstractController
{
    private const HARDCODED_DRIVER_ID = 6;


    public function __construct(
        private readonly AnnouncementService $announcementService,
        private readonly AnnouncementRepository $announcementRepository,
        private readonly DriverRepository $driverRepository,
        private readonly PaginatorInterface $paginator

    ) {
    }


#[Route('/new', name: 'app_transporter_announcement_new', methods: ['GET', 'POST'])]
public function new(Request $request): Response
{
    $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
    if (!$driver) {
        throw $this->createNotFoundException('Driver not found');
    }

    $announcement = new Announcement();
    $announcement->setDriver($driver);
    $announcement->setStatus(true);

    $form = $this->createForm(TransporterAnnouncementType::class, $announcement, [
        'validation_groups' => ['Default']
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            try {
                // Filtrer les bad words avant d’enregistrer
                $badWordsPath = $this->getParameter('kernel.project_dir') . '/public/data/bad_words.csv';
                $badWords = BadWordFilter::loadBadWords($badWordsPath);

                $announcement->setTitle(
                    BadWordFilter::filterBadWords($announcement->getTitle(), $badWords)
                );
                $announcement->setContent(
                    BadWordFilter::filterBadWords($announcement->getContent(), $badWords)
                );

                $this->announcementService->createAnnouncement(
                    $driver,
                    $announcement->getTitle(),
                    $announcement->getContent(),
                    $announcement->getZone(),
                    $announcement->getDate(),
                    $announcement->isStatus()
                );

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Annonce créée avec succès!',
                        'redirectUrl' => $this->generateUrl('app_transporter_announcement_list')
                    ]);
                }

                $this->addFlash('success', 'Annonce créée avec succès!');
                return $this->redirectToRoute('app_transporter_announcement_list');
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Erreur système: ' . $e->getMessage()
                    ], 500);
                }
                $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            }
        } else {
            $errors = $this->getFormErrors($form);

            foreach ($errors as $field => $message) {
                $this->addFlash('error', is_array($message) ? implode(' ', $message) : $message);
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Veuillez corriger les erreurs',
                    'errors' => $errors
                ], 422);
            }
        }
    }

    return $this->render('front/announcement/transporter/add.html.twig', [
        'form' => $form->createView()
    ]);
}

    
    private function getFormErrors($form): array
    {
        $errors = [];
        
        // Global form errors
        foreach ($form->getErrors() as $error) {
            $errors['_global'][] = $error->getMessage();
        }
        
        // Field-specific errors
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errors[$child->getName()] = $error->getMessage();
                }
            }
        }
        
        return $errors;
    }

    #[Route('/', name: 'app_transporter_announcement_list', methods: ['GET'])]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
        
        // Récupérer les paramètres de filtrage
        $keyword = trim($request->query->get('keyword', ''));
        $zone = $request->query->get('zone');
        $date = $request->query->get('date');

        // Construire la requête de base
        $qb = $this->announcementRepository->createQueryBuilder('a')
            ->where('a.driver = :driver')
            ->setParameter('driver', $driver);

        // Appliquer les filtres
        if ($keyword) {
            $qb->andWhere('a.title LIKE :keyword OR a.content LIKE :keyword')
               ->setParameter('keyword', '%'.$keyword.'%');
        }

        if ($zone) {
            $qb->andWhere('a.zone = :zone')
               ->setParameter('zone', $zone);
        }

        if ($date) {
            $qb->andWhere('DATE(a.date) = :date')
               ->setParameter('date', new \DateTime($date));
        }

        $query = $qb->orderBy('a.date', 'DESC')->getQuery();
        
        $announcements = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        // Pour les requêtes AJAX
        if ($request->isXmlHttpRequest()) {
            return $this->render('front/announcement/transporter/_announcement_list.html.twig', [
                'announcements' => $announcements
            ]);
        }

        return $this->render('front/announcement/transporter/list.html.twig', [
            'announcements' => $announcements,
            'zones' => Zone::cases()
        ]);
    }

#[Route('/{id}/delete', name: 'app_transporter_announcement_delete', methods: ['POST'])]
public function delete(Request $request, Announcement $announcement): Response
{
    if (!$this->isCsrfTokenValid('delete'.$announcement->getIdAnnouncement(), $request->request->get('_token'))) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $this->addFlash('error', 'Invalid CSRF token');
        return $this->redirectToRoute('app_transporter_announcement_list');
    }

    try {
        $this->announcementService->deleteAnnouncement($announcement);
        
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Announcement deleted successfully',
                'redirectUrl' => $this->generateUrl('app_transporter_announcement_list')
            ]);
        }
        
        $this->addFlash('success', 'Announcement deleted successfully');
    } catch (\Exception $e) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'error' => 'Error deleting announcement: '.$e->getMessage()
            ], 500);
        }
        $this->addFlash('error', 'Error deleting announcement: '.$e->getMessage());
    }

    return $this->redirectToRoute('app_transporter_announcement_list');
}

#[Route('/{id}/details', name: 'app_transporter_announcement_details', methods: ['GET'])]
public function details(int $id, AnnouncementRepository $announcementRepository): JsonResponse
{
    $announcement = $announcementRepository->find($id);
    
    if (!$announcement) {
        return new JsonResponse(['error' => 'Announcement not found'], 404);
    }

    return new JsonResponse([
        'title' => $announcement->getTitle(),
        'content' => $announcement->getContent(),
        'zone' => $announcement->getZone()->value,
        'date' => $announcement->getDate()->format('d M Y, H:i'),
        'status' => $announcement->isStatus()
    ]);
}

#[Route('/{id}/edit', name: 'app_transporter_announcement_edit', methods: ['GET'])]
public function edit(int $id, Request $request): Response
{
    $announcement = $this->announcementRepository->findOneBy(['id_announcement' => $id]);
    
    if (!$announcement) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Announcement not found'], 404);
        }
        throw $this->createNotFoundException('Announcement not found');
    }

    $form = $this->createForm(TransporterAnnouncementType::class, $announcement);
    
    if ($request->isXmlHttpRequest()) {
        return $this->render('front/announcement/transporter/_partials/edit_modal_content.html.twig', [
            'form' => $form->createView(),
            'announcement' => $announcement
        ]);
    }

    return $this->render('front/announcement/transporter/edit.html.twig', [
        'form' => $form->createView(),
        'announcement' => $announcement
    ]);
}

#[Route('/{id}/update', name: 'app_transporter_announcement_update', methods: ['POST'])]
public function update(Request $request, int $id): Response
{
    $announcement = $this->announcementRepository->findOneBy(['id_announcement' => $id]);
    if (!$announcement) {
        return new JsonResponse(['error' => 'Announcement not found'], 404);
    }

    $form = $this->createForm(TransporterAnnouncementType::class, $announcement);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            try {
                $this->announcementService->updateAnnouncement($announcement);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Announcement updated successfully!',
                    'announcement' => [
                        'id' => $announcement->getIdAnnouncement(),
                        'title' => $announcement->getTitle(),
                        'content' => $announcement->getContent(),
                        'zone' => $announcement->getZone()->value,
                        'date' => $announcement->getDate()->format('d M Y, H:i'),
                        'status' => $announcement->isStatus()
                    ]
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[$error->getOrigin()->getName()] = $error->getMessage();
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Please correct the errors',
            'errors' => $errors
        ], 422);
    }

    return new JsonResponse([
        'success' => false,
        'message' => 'Invalid form submission'
    ], 400);
}

#[Route('/generate-content', name: 'app_announcement_generate_content', methods: ['POST'])]
public function generateContent(Request $request, OpenAIService $openAIService): JsonResponse
{
    $userContent = $request->request->get('content', '');
    
    // Utilisation du contenu utilisateur tel quel, le service détectera la langue
    $prompt = $userContent;
    
    // Alternative avec langue explicite si préféré:
    // $language = $request->request->get('language', 'auto');
    
    try {
        $generatedText = $openAIService->generateText($prompt, 'auto');
        return $this->json([
            'success' => true,
            'content' => $generatedText
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => 'Failed to generate text: ' . $e->getMessage()
        ], 500);
    }
}

#[Route('/generate-titles', name: 'app_announcement_generate_titles', methods: ['POST'])]
public function generateTitles(Request $request, OpenAIService $openAIService): JsonResponse
{
    $content = $request->request->get('content', '');
    $language = $request->request->get('language', 'auto');

    try {
        $suggestions = $openAIService->generateTitleSuggestions($content, $language);
        return $this->json([
            'success' => true,
            'titles' => $suggestions
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}



#[Route('/search', name: 'app_transporter_announcement_search', methods: ['GET'])]
public function search(Request $request, PaginatorInterface $paginator): Response
{
    // 1. Récupération du transporteur
    $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
    if (!$driver) {
        throw $this->createNotFoundException('Driver not found');
    }

    // 2. Récupération des paramètres
    $keyword = trim($request->query->get('keyword', ''));
    $zone = $request->query->get('zone');
    $date = $request->query->get('date');

    // 3. Validation de la date
    $dateObj = null;
    if ($date) {
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            $dateObj = null;
        }
    }

    // 4. Construction de la requête AVEC filtre transporteur
    $query = $this->announcementService->getFilteredQueryBuilder($keyword, $zone, $dateObj)
        ->andWhere('a.driver = :driver')
        ->setParameter('driver', $driver);

    // 5. Pagination
    $announcements = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        6// Items par page pour transporteur
    );

    // 6. Gestion réponse AJAX
    if ($request->isXmlHttpRequest()) {
        $html = $this->renderView('front/announcement/transporter/_announcement_list.html.twig', [
            'announcements' => $announcements
        ]);
        return new JsonResponse(['html' => $html]);
    }

    // 7. Retour normal
    return $this->render('front/announcement/transporter/list.html.twig', [
        'announcements' => $announcements,
        'zones' => Zone::cases(), 
        'filters' => [
            'keyword' => $keyword,
            'zone' => $zone,
            'date' => $date
        ]
    ]);
}
    
}