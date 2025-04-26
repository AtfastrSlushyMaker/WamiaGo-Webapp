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
        // Get filter parameters
        $filters = [];
        
        // Zone filter
        if ($request->query->has('zone')) {
            $filters['zone'] = $request->query->get('zone');
        }
        
        // Status filter
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->getBoolean('status');
        }
        
        // Get query
        $query = !empty($filters) 
            ? $announcementRepo->createQueryByFilters($filters)
            : $announcementRepo->createQueryBuilder('a')
                ->orderBy('a.date', 'DESC')
                ->getQuery();
        
        // Paginate results
        $announcements = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Items per page
        );
        
        return $this->render('back-office/Announcements/index.html.twig', [
            'announcements' => $announcements,
            'filters' => $filters
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
}