<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

class ExportService
{
    private $pdf;
    private $twig;

    public function __construct(Pdf $pdf, Environment $twig)
    {
        $this->pdf = $pdf;
        $this->twig = $twig;
        
        $this->pdf->setOptions([
            'page-size' => 'A4',
            'margin-top' => 15,
            'margin-right' => 15,
            'margin-bottom' => 15,
            'margin-left' => 15,
            'encoding' => 'UTF-8',
            'lowquality' => false,
            'no-print-media-type' => true,
            'disable-smart-shrinking' => false,
            'print-media-type' => false, 
            'no-outline' => true,
            'disable-javascript' => false,
            'disable-local-file-access' => false,
            'javascript-delay' => 1000,

        ]);
    }

    /**
     * Export data to CSV format
     */
    public function exportToCsv(array $headers, array $data, string $filename): Response
    {
        $csvData = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $csvData .= implode(',', array_map(function($cell) {
                
                if (is_string($cell) && (strpos($cell, ',') !== false || strpos($cell, '"') !== false)) {
                    return '"' . str_replace('"', '""', $cell) . '"';
                }
                return $cell;
            }, $row)) . "\n";
        }

        $response = new Response($csvData);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename . '.csv'
        );
        
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        
        return $response;
    }

    /**
     * Export data to Excel format
     */
    public function exportToExcel(array $headers, array $data, string $filename, array $columnStyles = [], string $title = ''): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        

        if ($title) {
            
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . $this->getColumnLetter(count($headers)) . '1');
            
            // Style title
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
            
            
            $sheet->insertNewRowBefore(2);
      
            $this->setExcelHeaders($sheet, $headers, 3);
        } else {

            $this->setExcelHeaders($sheet, $headers, 1);
        }
   
        $startRow = $title ? 4 : 2;
        
        // Add data rows
        foreach ($data as $rowIndex => $rowData) {
            $row = $startRow + $rowIndex;
            
            foreach ($rowData as $columnIndex => $cellValue) {
                $column = $this->getColumnLetter($columnIndex + 1);
                $sheet->setCellValue($column . $row, $cellValue);
        
                if (isset($columnStyles[$columnIndex])) {
                    $cellStyle = $sheet->getStyle($column . $row);
                    
                    if (isset($columnStyles[$columnIndex]['format'])) {
                        $cellStyle->getNumberFormat()->setFormatCode($columnStyles[$columnIndex]['format']);
                    }
                    
                    if (isset($columnStyles[$columnIndex]['alignment'])) {
                        $cellStyle->getAlignment()->setHorizontal($columnStyles[$columnIndex]['alignment']);
                    }
                }
            }
        }
        

        foreach (range('A', $this->getColumnLetter(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);
   
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        $response = new Response($content);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename . '.xlsx'
        );
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        
        return $response;
    }

    /**
     * Export data to PDF format using a Twig template

     */
    public function exportToPdf(string $templatePath, array $data, string $filename): Response
    {
        // Render the template
        $html = $this->twig->render($templatePath, $data);
        
        
        $pdf = $this->pdf->getOutputFromHtml($html);
        
     
        $response = new Response($pdf);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename . '.pdf'
        );
        
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        
        return $response;
    }

    /**
     * Get Excel column letter from index
   
     */
    private function getColumnLetter(int $columnIndex): string
    {
        if ($columnIndex <= 0) {
            return '';
        }
        
        $dividend = $columnIndex;
        $columnName = '';
        
        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $columnName = chr(65 + $modulo) . $columnName;
            $dividend = (int)(($dividend - $modulo) / 26);
        }
        
        return $columnName;
    }

    /**
     * Set and style Excel headers
     */
    private function setExcelHeaders($sheet, array $headers, int $headerRow): void
    {
        foreach ($headers as $index => $header) {
            $column = $this->getColumnLetter($index + 1);
            $sheet->setCellValue($column . $headerRow, $header);
        }

        $headerRange = 'A' . $headerRow . ':' . $this->getColumnLetter(count($headers)) . $headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '4285F4',
                ],
            ],
            'font' => [
                'color' => [
                    'rgb' => 'FFFFFF', 
                ],
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }
}