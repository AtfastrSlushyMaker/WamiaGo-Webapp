<?php

namespace App\Controller\Admin;

use App\Entity\Relocation;
use App\Repository\RelocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\PdfGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/admin/relocations')]
class RelocationsController extends AbstractController
{
   #[Route('/', name: 'admin_relocations_index', methods: ['GET'])]
public function index(
    Request $request,
    RelocationRepository $relocationRepo,
    PaginatorInterface $paginator
): Response {
    $searchParams = [
        'keyword' => $request->query->get('keyword'),
        'status' => $request->query->get('status'),
        'date' => $request->query->get('date')
    ];

    $query = $relocationRepo->createSearchQueryBuilder($searchParams);

    $relocations = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10,
        ['pageParameterName' => 'page']
    );

    if ($request->isXmlHttpRequest()) {
        return $this->render('back-office/Relocations/_partials/_list.html.twig', [
            'relocations' => $relocations
        ]);
    }

    return $this->render('back-office/Relocations/index.html.twig', [
        'relocations' => $relocations
    ]);
}

    #[Route('/{id}', name: 'admin_relocations_show', methods: ['GET'])]
    public function show(Relocation $relocation): Response
    {
        return $this->render('back-office/Relocations/show.html.twig', [
            'relocation' => $relocation,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_relocations_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        RelocationRepository $relocationRepo,
        EntityManagerInterface $em,
        int $id 
    ): Response {
        $relocation = $relocationRepo->find($id);
        
        if (!$relocation) {
            $this->addFlash('error', 'Relocation not found');
            return $this->redirectToRoute('admin_relocations_index');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete'.$relocation->getIdRelocation(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('admin_relocations_index');
        }

        try {
            $em->remove($relocation);
            $em->flush();
            
            $this->addFlash('success', 'Relocation successfully deleted');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting relocation: '.$e->getMessage());
        }

        return $this->redirectToRoute('admin_relocations_index');
    }

    

#[Route('/export/all-pdf', name: 'admin_relocations_export_all', methods: ['GET'])]
public function exportAllToPdf(
    RelocationRepository $relocationRepo,
    PdfGenerator $pdfGenerator
): Response {
    try {
        // Récupérer toutes les relocalisations
        $relocations = $relocationRepo->findAll();
        
        // Rendre le template HTML
        $html = $this->renderView('pdf/all_relocations.html.twig', [
            'relocations' => $relocations
        ]);
        
        // Générer le nom du fichier
        $filename = 'relocations_export_' . date('Y-m-d') . '.pdf';
        
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
        return $this->redirectToRoute('admin_relocations_index');
    }
}


}