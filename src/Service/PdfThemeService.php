<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfThemeService
{
    private Dompdf $pdf;
    private array $theme;

    public function __construct()
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('defaultMediaType', 'print');
        $options->set('dpi', 150);
        
        $this->pdf = new Dompdf($options);
        $this->theme = [
            'primaryColor' => '#2c3e50',
            'secondaryColor' => '#3498db',
            'accentColor' => '#e74c3c',
            'fontFamily' => 'Helvetica, Arial, sans-serif',
            'headerStyle' => [
                'background' => 'linear-gradient(135deg, #2c3e50 0%, #3498db 100%)',
                'color' => '#ffffff',
                'padding' => '30px',
                'borderRadius' => '5px 5px 0 0'
            ],
            'tableStyle' => [
                'borderColor' => '#dee2e6',
                'headerBg' => '#f8f9fa',
                'stripedBg' => '#f2f2f2'
            ],
            'footerStyle' => [
                'borderTop' => '1px solid #dee2e6',
                'padding' => '20px',
                'textAlign' => 'center',
                'color' => '#6c757d'
            ]
        ];
    }

    public function generatePdfResponse(string $html, string $filename): Response
    {
        $this->pdf->loadHtml($this->wrapWithStyle($html));
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();

        return new Response(
            $this->pdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
                'Cache-Control' => 'private, max-age=0, must-revalidate'
            ]
        );
    }

    private function wrapWithStyle(string $content): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body {
                        font-family: {$this->theme['fontFamily']};
                        line-height: 1.6;
                        color: #333;
                        margin: 0;
                        padding: 20px;
                    }
                    .header {
                        background: {$this->theme['headerStyle']['background']};
                        color: {$this->theme['headerStyle']['color']};
                        padding: {$this->theme['headerStyle']['padding']};
                        margin-bottom: 30px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    th {
                        background: {$this->theme['tableStyle']['headerBg']};
                        padding: 12px;
                        border: 1px solid {$this->theme['tableStyle']['borderColor']};
                        text-align: left;
                    }
                    td {
                        padding: 10px;
                        border: 1px solid {$this->theme['tableStyle']['borderColor']};
                    }
                    tr:nth-child(even) {
                        background: {$this->theme['tableStyle']['stripedBg']};
                    }
                    .footer {
                        border-top: {$this->theme['footerStyle']['borderTop']};
                        padding: {$this->theme['footerStyle']['padding']};
                        text-align: {$this->theme['footerStyle']['textAlign']};
                        color: {$this->theme['footerStyle']['color']};
                        margin-top: 30px;
                    }
                    .page-number:before {
                        content: counter(page);
                    }
                </style>
            </head>
            <body>
                $content
                <div class="footer">
                    Generated on: {$this->getCurrentDateTime()}
                    <br>
                    Page <span class="page-number"></span>
                </div>
            </body>
            </html>
        HTML;
    }

    private function getCurrentDateTime(): string
    {
        return (new \DateTime())->format('F j, Y \a\t H:i:s');
    }
}