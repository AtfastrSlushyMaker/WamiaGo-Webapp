<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PdfGenerator
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function generatePdfFromHtml(string $html, string $filename = 'document.pdf'): string
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('isHtml5ParserEnabled', true);
    
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        
        // Remplacer le callback problématique par cette version simplifiée
        $dompdf->setCallbacks([
            'event' => 'end_frame',
            'f' => function($frame, $event, $canvas) {
                if ($event === 'end_frame') {
                    $node = $frame->get_node();
                    if ($node && $node->nodeName === 'span') {
                        if ($node->getAttribute('class') === 'pageNumber') {
                            $node->nodeValue = $canvas->get_page_number();
                        }
                        if ($node->getAttribute('class') === 'totalPages') {
                            $node->nodeValue = $canvas->get_page_count();
                        }
                    }
                }
            }
        ]);
    
        $dompdf->render();
    
        $publicDirectory = $this->params->get('kernel.project_dir') . '/public/pdf';
        if (!file_exists($publicDirectory)) {
            mkdir($publicDirectory, 0777, true);
        }
    
        $pdfFilepath = $publicDirectory . '/' . $filename;
        file_put_contents($pdfFilepath, $dompdf->output());
    
        return $filename;
    }
}