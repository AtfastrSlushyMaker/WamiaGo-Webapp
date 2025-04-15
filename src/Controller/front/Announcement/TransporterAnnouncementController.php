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


#[Route('/transporter/announcements')]
class TransporterAnnouncementController extends AbstractController
{
    private const HARDCODED_DRIVER_ID = 6;


    public function __construct(
        private readonly AnnouncementService $announcementService,
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
        //$announcement->setDate((new \DateTime())); 
    
        $form = $this->createForm(TransporterAnnouncementType::class, $announcement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
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
    foreach ($form->getErrors(true) as $error) {
        $fieldName = $error->getOrigin()->getName();
        if (!isset($errors[$fieldName])) {
            $errors[$fieldName] = $error->getMessage();
        } else {
            // Si plusieurs erreurs pour le même champ, les concaténer
            if (is_array($errors[$fieldName])) {
                $errors[$fieldName][] = $error->getMessage();
            } else {
                $errors[$fieldName] = [$errors[$fieldName], $error->getMessage()];
            }
        }
    }
    return $errors;
}

    #[Route('/', name: 'app_transporter_announcement_list', methods: ['GET'])]
public function list(Request $request, PaginatorInterface $paginator): Response
{
    $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
    if (!$driver) {
        throw $this->createNotFoundException('Driver not found');
    }

    $query = $this->announcementService->getAnnouncementsQueryByDriver($driver);
    
    $announcements = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        6 // Items per page
    );

    return $this->render('front/announcement/transporter/list.html.twig', [
        'announcements' => $announcements
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

#[Route('/{id}/edit', name: 'app_transporter_announcement_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    int $id,
    AnnouncementRepository $announcementRepository,
    EntityManagerInterface $em
): Response {
    $announcement = $announcementRepository->find($id);
    
    if (!$announcement) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Announcement not found'], 404);
        }
        throw $this->createNotFoundException('Announcement not found');
    }

    $form = $this->createForm(TransporterAnnouncementType::class, $announcement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Announcement updated successfully!',
                    'html' => $this->renderView('front/announcement/transporter/_announcement_card.html.twig', [
                        'announcement' => $announcement
                    ])
                ]);
            }

            $this->addFlash('success', 'Announcement updated successfully!');
            return $this->redirectToRoute('app_transporter_announcement_list');
            
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            $this->addFlash('error', 'Error updating announcement');
        }
    }

    $view = $request->isXmlHttpRequest() 
        ? 'front/announcement/transporter/_edit_form.html.twig'
        : 'front/announcement/transporter/edit.html.twig';

    return $this->render($view, [
        'form' => $form->createView(),
        'announcement' => $announcement
    ], new Response(
        null,
        $form->isSubmitted() && !$form->isValid() ? 422 : 200
    ));
}
    
}