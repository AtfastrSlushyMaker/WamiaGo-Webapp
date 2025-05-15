<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/reclamation')]
final class ReclamationController extends AbstractController
{
    #[Route('/{id_reclamation}/detail', name: 'app_reclamation_detail', methods: ['GET'])]
    public function detail(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/detail.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }
    
    #[Route('/list', name: 'app_reclamation_list', methods: ['GET'])]
    public function getAllReclamation(Request $request, ReclamationRepository $reclamationRepository, PaginatorInterface $paginator): Response
    {
        $query = $reclamationRepository->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC')
            ->getQuery();
            
        $pagination = $paginator->paginate(
            $query, // Query to paginate
            $request->query->getInt('page', 1), // Current page number, default to 1
            10 // Number of items per page
        );
        
        // Handle AJAX request for pagination
        if ($request->isXmlHttpRequest()) {
            return $this->render('reclamation/reclamation_list_content.html.twig', [
                'reclamations' => $pagination,
            ]);
        }
        
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $pagination,
        ]);
    }

    // Add a compatibility route for the old URL pattern
    #[Route('/getAllReclamation', name: 'app_reclamation_list_old', methods: ['GET'])]
    public function getAllReclamationOld(Request $request): Response
    {
        // Redirect to the new route
        return $this->redirectToRoute('app_reclamation_list', $request->query->all());
    }

    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(): Response
    {
        // Redirige vers la route qui liste les réclamations
        return $this->redirectToRoute('app_reclamation_list');
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/create', name: 'reclamation_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Since we don't have session implementation, we'll use a fixed user ID
        // Fetch the user from the database
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find(1);

        if (!$user) {
            throw $this->createNotFoundException('User with ID 1 not found');
        }
        
        $reclamation = new Reclamation();
        $reclamation->setUser($user);
        $reclamation->setTitle($request->request->get('title'));
        $reclamation->setContent($request->request->get('content'));
        $reclamation->setDate(new \DateTime());
        $reclamation->setStatus(false);

        $entityManager->persist($reclamation);
        $entityManager->flush();

        $this->addFlash('success', 'Your message has been sent successfully!');
        return $this->redirectToRoute('app_front_home');
    }

    #[Route('/{id_reclamation}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_reclamation}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId_reclamation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export-pdf', name: 'app_reclamation_export_pdf', methods: ['GET'])]
    public function exportPdf(ReclamationRepository $reclamationRepository): Response
    {
        // Get all reclamations
        $reclamations = $reclamationRepository->findAll();

        // Create simple HTML without images to avoid GD extension issues
        $html = '<html><head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $html .= '<title>Liste des réclamations - WamiaGo</title>';
        $html .= '<style>
            body { font-family: DejaVu Sans, sans-serif; padding: 10px; }
            h1 { color: #4e73df; text-align: center; font-size: 24px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; }
            .title { font-size: 22px; font-weight: bold; color: #4e73df; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #4e73df; color: white; padding: 10px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
            .status-true { color: green; font-weight: bold; }
            .status-false { color: red; font-weight: bold; }
        </style>';
        $html .= '</head><body>';
        
        // Header with text-based logo to avoid GD issues
        $html .= '<div class="header">';
        $html .= '<div class="title">WamiaGo</div>';
        $html .= '<h1>Liste des réclamations</h1>';
        $html .= '<p>Généré le ' . (new \DateTime())->format('d/m/Y à H:i') . '</p>';
        $html .= '</div>';
        
        // Table of reclamations
        $html .= '<table>';
        $html .= '<thead><tr><th>ID</th><th>Utilisateur</th><th>Titre</th><th>Date</th><th>Statut</th></thead>';
        $html .= '<tbody>';
        
        foreach ($reclamations as $reclamation) {
            $html .= '<tr>';
            $html .= '<td>#' . $reclamation->getId_reclamation() . '</td>';
            $html .= '<td>' . ($reclamation->getUser() ? $reclamation->getUser()->getName() : 'N/A') . '</td>';
            $html .= '<td>' . $reclamation->getTitle() . '</td>';
            $html .= '<td>' . $reclamation->getDate()->format('d/m/Y') . '</td>';
            $html .= '<td class="status-' . ($reclamation->isStatus() ? 'true' : 'false') . '">';
            $html .= $reclamation->isStatus() ? 'Traité' : 'Non traité';
            $html .= '</td></tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Footer
        $html .= '<div class="footer">';
        $html .= '<p>WamiaGo - Tous droits réservés &copy; ' . date('Y') . '</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        // Configure Dompdf with minimal options
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        
        try {
            // Create Dompdf instance
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            
            // Render the PDF
            $dompdf->render();
            
            // Stream the file as download
            $output = $dompdf->output();
            
            return new Response(
                $output,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="reclamations-list-' . date('Y-m-d') . '.pdf"',
                ]
            );
        } catch (\Exception $e) {
            // If PDF generation fails, log the error and redirect
            error_log('PDF Generation Error: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_reclamation_list');
        }
    }
}