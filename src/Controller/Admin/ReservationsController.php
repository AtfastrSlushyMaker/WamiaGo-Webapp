<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\PdfGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/reservations')]
class ReservationsController extends AbstractController
{
    #[Route('/', name: 'admin_reservations_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ReservationRepository $reservationRepo,
        PaginatorInterface $paginator
    ): Response {
        // Get filter parameters
        $filters = [];
        
        // Status filter
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }
        
        // Get query
        $query = !empty($filters) 
            ? $reservationRepo->createQueryByFilters($filters)
            : $reservationRepo->createQueryBuilder('r')
                ->orderBy('r.date', 'DESC')
                ->getQuery();
        
        // Paginate results
        $reservations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Items per page
        );
        
        return $this->render('back-office/Reservations/index.html.twig', [
            'reservations' => $reservations,
            'filters' => $filters
        ]);
    }

    #[Route('/{id}', name: 'admin_reservations_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('back-office/Reservations/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_reservations_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Reservation $reservation, 
        EntityManagerInterface $em,
        ReservationRepository $reservationRepo
    ): Response {
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found');
            return $this->redirectToRoute('admin_reservations_index');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reservation->getIdReservation(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('admin_reservations_index');
        }

        try {
            foreach ($reservation->getRelocations() as $relocation) {
                $em->remove($relocation);
            }

            $em->remove($reservation);
            $em->flush();

            $this->addFlash('success', 'Reservation successfully deleted');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting reservation: '.$e->getMessage());
        }

        return $this->redirectToRoute('admin_reservations_index');
    }

    
#[Route('/{id}/pdf', name: 'admin_reservations_pdf', methods: ['GET'])]
public function generatePdf(
    Reservation $reservation,
    PdfGenerator $pdfGenerator,
    Request $request
): Response {
    try {
        // Rendre le template HTML
        $html = $this->renderView('pdf/reservation.html.twig', [
            'reservation' => $reservation
        ]);
        
        // Générer le nom du fichier
        $filename = 'reservation_' . $reservation->getIdReservation() . '.pdf';
        
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
        return $this->redirectToRoute('admin_reservations_show', ['id' => $reservation->getIdReservation()]);
    }
}

#[Route('/export/all-pdf', name: 'admin_reservations_export_all', methods: ['GET'])]
public function exportAllToPdf(
    ReservationRepository $reservationRepo,
    PdfGenerator $pdfGenerator
): Response {
    try {
        // Récupérer toutes les réservations
        $reservations = $reservationRepo->findAll();
        
        // Rendre le template HTML
        $html = $this->renderView('pdf/all_reservations.html.twig', [
            'reservations' => $reservations
        ]);
        
        // Générer le nom du fichier
        $filename = 'all_reservations_' . date('Y-m-d') . '.pdf';
        
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
        return $this->redirectToRoute('admin_reservations_index');
    }
}
}