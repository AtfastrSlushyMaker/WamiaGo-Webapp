<?php

namespace App\Service;

use Knp\Snappy\Pdf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use App\Entity\Announcement;

class PdfGeneratorService
{
    private Pdf $pdf;
    private Environment $twig;
    private string $projectDir;
    private $pdfThemeService;
    public function __construct(Pdf $pdf, Environment $twig, ParameterBagInterface $params, $pdfThemeService)
    {
        $this->pdf = $pdf;
        $this->twig = $twig;
        $this->projectDir = $params->get('kernel.project_dir');
        $this->pdfThemeService = $pdfThemeService;
    }
    

    public function generateAnnouncementPdf(Announcement $announcement): string
    {
        $this->pdfThemeService->applyProfessionalTheme();
    
    $html = $this->twig->render('pdf/announcement.html.twig', [
        'announcement' => $announcement,
        'theme' => $this->pdfThemeService->getCurrentTheme()
    ]);
        
        $html = $this->twig->render('pdf/announcement.html.twig', [
            'announcement' => $announcement
        ]);

        $filename = sprintf('announcement_%d_%s.pdf', 
            $announcement->getId_announcement(), 
            (new \DateTime())->format('YmdHis')
        );

        $outputPath = $this->projectDir.'/public/pdf/'.$filename;
        
        $this->pdf->generateFromHtml($html, $outputPath, [], true);

        return $filename;
    }

    public function generateWithCustomOptions(Announcement $announcement, array $options = []): string
{
    $defaultOptions = [
        'header-html' => $this->twig->render('pdf/_header.html.twig'),
        'footer-html' => $this->twig->render('pdf/_footer.html.twig'),
        'margin-top' => '20mm',
        'margin-bottom' => '20mm',
        'dpi' => 300,
        'print-media-type' => true
    ];

    $options = array_merge($defaultOptions, $options);
    
    $html = $this->twig->render('pdf/announcement.html.twig', [
        'announcement' => $announcement
    ]);

    $filename = sprintf('announcement_%d_%s.pdf', 
        $announcement->getId_announcement(), 
        (new \DateTime())->format('YmdHis')
    );

    $outputPath = $this->projectDir.'/public/pdf/'.$filename;
    
    $this->pdf->generateFromHtml($html, $outputPath, $options, true);

    return $filename;
}

public function generateMultiple(array $announcements): string
{
    $html = '';
    foreach ($announcements as $announcement) {
        $html .= $this->twig->render('pdf/announcement_item.html.twig', [
            'announcement' => $announcement
        ]);
    }

    $filename = 'announcements_'.(new \DateTime())->format('YmdHis').'.pdf';
    $outputPath = $this->projectDir.'/public/pdf/'.$filename;
    
    $this->pdf->generateFromHtml($html, $outputPath, [], true);

    return $filename;
}
}