<?php

namespace App\Service\Export;

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
        
        // Configure PDF options directly here instead of in knp_snappy.yaml
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
     *
     * @param array $headers Column headers
     * @param array $data Array of data rows
     * @param string $filename Base filename without extension
     * @return Response
     */
    public function exportToCsv(array $headers, array $data, string $filename): Response
    {
        $csvData = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $csvData .= implode(',', array_map(function($cell) {
                // Escape quotes and wrap with quotes if needed
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
     *
     * @param array $headers Column headers
     * @param array $data Array of data rows
     * @param string $filename Base filename without extension
     * @param array $columnStyles Column specific styles [column_index => [style => value]]
     * @param string $title Optional title for the sheet
     * @return Response
     */
    public function exportToExcel(array $headers, array $data, string $filename, array $columnStyles = [], string $title = ''): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title if provided
        if ($title) {
            // Add title row
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
            
            // Add some space
            $sheet->insertNewRowBefore(2);
            
            // Set headers at row 3
            $this->setExcelHeaders($sheet, $headers, 3);
        } else {
            // Set headers at row 1
            $this->setExcelHeaders($sheet, $headers, 1);
        }
        
        // Calculate start row for data based on title existence
        $startRow = $title ? 4 : 2;
        
        // Add data rows
        foreach ($data as $rowIndex => $rowData) {
            $row = $startRow + $rowIndex;
            
            foreach ($rowData as $columnIndex => $cellValue) {
                $column = $this->getColumnLetter($columnIndex + 1);
                $sheet->setCellValue($column . $row, $cellValue);
                
                // Apply column-specific styles if defined
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
        
        // Auto-size columns for better readability
        foreach (range('A', $this->getColumnLetter(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);
        
        // Create and return response
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
     *
     * @param string $templatePath Path to the Twig template
     * @param array $data Data to pass to the template
     * @param string $filename Base filename without extension
     * @return Response
     */
    public function exportToPdf(string $templatePath, array $data, string $filename): Response
    {
        // Render the template
        $html = $this->twig->render($templatePath, $data);
        
        // Generate PDF from HTML
        $pdf = $this->pdf->getOutputFromHtml($html);
        
        // Create response
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
     *
     * @param int $columnIndex 1-based column index
     * @return string Column letter (A, B, C, ..., Z, AA, AB, ...)
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
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array $headers
     * @param int $headerRow Row number for headers
     */
    private function setExcelHeaders($sheet, array $headers, int $headerRow): void
    {
        foreach ($headers as $index => $header) {
            $column = $this->getColumnLetter($index + 1);
            $sheet->setCellValue($column . $headerRow, $header);
        }
        
        // Style headers
        $headerRange = 'A' . $headerRow . ':' . $this->getColumnLetter(count($headers)) . $headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '4285F4', // Google blue
                ],
            ],
            'font' => [
                'color' => [
                    'rgb' => 'FFFFFF', // White text
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