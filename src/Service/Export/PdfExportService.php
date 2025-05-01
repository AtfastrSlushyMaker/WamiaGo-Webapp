<?php

namespace App\Service\Export;

use App\Entity\Announcement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfExportService
{
    public function __construct(
        private readonly Environment $twig
    ) {}

    public function generateAnnouncementsPdf(array $announcements, array $filters = []): Response
    {
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        // Create Dompdf instance
        $dompdf = new Dompdf($options);
        
        try {
            // Generate HTML content
            $html = $this->twig->render('back-office/Announcements/export/pdf_template.html.twig', [
                'announcements' => $announcements,
                'filters' => $filters,
                'generated_at' => new \DateTime()
            ]);
            
            // Load HTML into Dompdf
            $dompdf->loadHtml($html);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
            
            // Render PDF
            $dompdf->render();
            
            // Generate filename
            $filename = $this->generateFilename($filters);
            
            // Return response with PDF content
            return new Response(
                $dompdf->output(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    private function generateFilename(array $filters): string
    {
        $parts = ['announcements'];
        
        if (!empty($filters['zone'])) {
            $parts[] = strtolower($filters['zone']);
        }
        
        if (!empty($filters['status'])) {
            $parts[] = $filters['status'] ? 'active' : 'all';
        }
        
        $parts[] = date('Y-m-d-His');
        
        return implode('_', $parts) . '.pdf';
    }
}