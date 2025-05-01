<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PdfGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Service\PdfThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Enum\Zone;

use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/announcements')]
class AnnouncementsController extends AbstractController
{
    #[Route('/', name: 'admin_announcements_index', methods: ['GET'])]
public function index(
    Request $request, 
    AnnouncementRepository $announcementRepo,
    PaginatorInterface $paginator
): Response {
    // Récupération des paramètres
    $keyword = trim($request->query->get('keyword', ''));
    $zone = $request->query->get('zone');
    $date = $request->query->get('date');

    // Construction de la requête
    $qb = $announcementRepo->createQueryBuilder('a')
        ->orderBy('a.date', 'DESC');

    if (!empty($keyword)) {
        $qb->andWhere('a.title LIKE :keyword OR a.content LIKE :keyword')
           ->setParameter('keyword', '%' . $keyword . '%');
    }

    if (!empty($zone)) {
        $qb->andWhere('a.zone = :zone')
           ->setParameter('zone', $zone);
    }

    if (!empty($date)) {
        $qb->andWhere('DATE(a.date) = :date')
           ->setParameter('date', $date);
    }

    // Pagination
    $announcements = $paginator->paginate(
        $qb->getQuery(),
        $request->query->getInt('page', 1),
        10
    );

    // Réponse AJAX
    if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
            'html' => $this->renderView('back-office/Announcements/_announcement_list.html.twig', [
                'announcements' => $announcements
            ]),
            'pagination' => $this->renderView('@KnpPaginator/Pagination/twitter_bootstrap_v4_pagination.html.twig', [
                'pagination' => $announcements
            ])
        ]);
    }

    return $this->render('back-office/Announcements/index.html.twig', [
        'announcements' => $announcements,
        'zones' => Zone::cases()
    ]);
}

    #[Route('/{id}', name: 'admin_announcements_show', methods: ['GET'])]
    public function show(Announcement $announcement): Response
    {
        return $this->render('back-office/Announcements/show.html.twig', [
            'announcement' => $announcement,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_announcements_delete', methods: ['POST'])]
    public function delete(Request $request, Announcement $announcement, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$announcement->getIdAnnouncement(), $request->request->get('_token'))) {
            $em->remove($announcement);
            $em->flush();
            $this->addFlash('success', 'Announcement successfully deleted');
        } else {
            $this->addFlash('error', 'Invalid CSRF token');
        }
    
        return $this->redirectToRoute('admin_announcements_index');
    }

   
#[Route('/{id}/pdf', name: 'admin_announcements_pdf', methods: ['GET'])]
public function generatePdf(
    Announcement $announcement,
    PdfGenerator $pdfGenerator,
    Request $request
): Response {
    try {
        // Rendre le template HTML
        $html = $this->renderView('pdf/announcement.html.twig', [
            'announcement' => $announcement
        ]);
        
        // Générer le nom du fichier
        $filename = 'announcement_' . $announcement->getId_announcement() . '.pdf';
        
        // Générer le PDF
        $pdfFile = $pdfGenerator->generatePdfFromHtml($html, $filename);
        
        // Soit afficher dans le navigateur, soit télécharger
        if ($request->query->get('download')) {
            return $this->file(
                $this->getParameter('kernel.project_dir') . '/public/pdf/' . $pdfFile,
                $filename,
                ResponseHeaderBag::DISPOSITION_ATTACHMENT
            );
        }
        
        return new Response(
            file_get_contents($this->getParameter('kernel.project_dir') . '/public/pdf/' . $pdfFile),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"'
            ]
        );
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Failed to generate PDF: ' . $e->getMessage());
        return $this->redirectToRoute('admin_announcements_show', ['id' => $announcement->getId_announcement()]);
    }
}

#[Route('/export/all-pdf', name: 'admin_announcements_export_all', methods: ['GET'])]
public function exportAllToPdf(
    AnnouncementRepository $announcementRepo,
    PdfGenerator $pdfGenerator
): Response {
    try {
        // Récupérer toutes les annonces
        $announcements = $announcementRepo->findAll();
        
        // Rendre le template HTML
        $html = $this->renderView('pdf/all_announcements.html.twig', [
            'announcements' => $announcements
        ]);
        
        // Générer le nom du fichier
        $filename = 'all_announcements_' . date('Y-m-d') . '.pdf';
        
        // Générer le PDF
        $pdfFile = $pdfGenerator->generatePdfFromHtml($html, $filename);
        
        // Télécharger le PDF
        return $this->file(
            $this->getParameter('kernel.project_dir') . '/public/pdf/' . $pdfFile,
            $filename,
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Failed to generate PDF: ' . $e->getMessage());
        return $this->redirectToRoute('admin_announcements_index');
    }
}

#[Route('/search', name: 'admin_announcements_search', methods: ['GET'])]
public function search(
    Request $request, 
    AnnouncementRepository $announcementRepo,
    PaginatorInterface $paginator
): Response {
    $keyword = trim($request->query->get('keyword', ''));
    $zone = $request->query->get('zone');
    $date = $request->query->get('date');

    try {
       
        if (method_exists($announcementRepo, 'createSearchQueryBuilder_admin')) {
            $qb = $announcementRepo->createSearchQueryBuilder_admin($keyword, $zone, $date);
        } else {
            // Si la méthode n'existe pas, utilisez le même code que la méthode index
            $qb = $announcementRepo->createQueryBuilder('a')
                ->orderBy('a.date', 'DESC');
                
            if (!empty($keyword)) {
                $qb->andWhere('a.title LIKE :keyword')
                   ->setParameter('keyword', '%' . $keyword . '%');
            }
            
            if (!empty($zone)) {
                $qb->andWhere('a.zone = :zone')
                   ->setParameter('zone', $zone);
            }
            
            if (!empty($date)) {
                $qb->andWhere('DATE(a.date) = :date')
                   ->setParameter('date', $date);
            }
        }
        
        $announcements = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

       
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'html' => $this->renderView('back-office/Announcements/_announcement_list.html.twig', [
                    'announcements' => $announcements
                ]),
                'pagination' => $this->renderView('@KnpPaginator/Pagination/twitter_bootstrap_v4_pagination.html.twig', [
                    'pagination' => $announcements,
                    'route' => 'admin_announcements_index',
                    'query' => $request->query->all()
                ])
            ]);
        }

        return $this->render('back-office/Announcements/index.html.twig', [
            'announcements' => $announcements,
            'zones' => Zone::cases()
        ]);
    } catch (\Exception $e) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
        
        $this->addFlash('error', 'Search error: ' . $e->getMessage());
        return $this->redirectToRoute('admin_announcements_index');
    }
}
}