<?php

namespace App\Controller\Admin\taxi;

use Dompdf\Dompdf;
use Dompdf\Options;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RequestService;
use App\Enum\REQUEST_STATUS;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Enum\RIDE_STATUS;
use App\Service\RideService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class TaxiManagementController extends AbstractController
{
    private RequestService $requestService;
    private RideService $rideService;
    private Dompdf $pdf; // Declare the $pdf property

    public function __construct(RequestService $requestService, RideService $rideService, Dompdf $pdf)
    {
        $this->requestService = $requestService;
        $this->rideService = $rideService; 
        $this->pdf = $pdf; // Initialize the $pdf property
    }

    #[Route('/admin/taxi-management', name: 'admin_taxi_management')]
    public function index(HttpRequest $request): Response
    {
        // Get search parameters from request
        $search = (string) $request->query->get('search', '');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort'); // Get sort parameter
    
        // Fetch all requests and rides
        $availableRequests = $this->requestService->getRealyAllRequest();
        $availableRides = $this->rideService->getAllRides();
    
        // Apply search filters to requests
        if ($search) {
            $availableRequests = array_filter($availableRequests, function ($request) use ($search) {
                return stripos($request->getUser()->getName(), $search) !== false ||
                       stripos($request->getDepartureLocation()->getAddress(), $search) !== false ||
                       stripos($request->getArrivalLocation()->getAddress(), $search) !== false ||
                       stripos($request->getStatus()->value, $search) !== false ||
                       stripos($request->getIdRequest(), $search) !== false;
            });
        }
    
        // Apply search filters to rides
        if ($search) {
            $availableRides = array_filter($availableRides, function ($ride) use ($search) {
                return stripos($ride->getRequest()->getUser()->getName(), $search) !== false ||
                       stripos($ride->getRequest()->getDepartureLocation()->getAddress(), $search) !== false ||
                       stripos($ride->getRequest()->getArrivalLocation()->getAddress(), $search) !== false ||
                       stripos($ride->getStatus()->value, $search) !== false ||
                       stripos($ride->getIdRide(), $search) !== false ||
                       stripos((string)$ride->getPrice(), $search) !== false ||
                       stripos((string)$ride->getDistance(), $search) !== false;
            });
        }
    
        // Filter requests by status
        if ($status) {
            $availableRequests = array_filter($availableRequests, function ($request) use ($status) {
                return $request->getStatus()->value === $status;
            });
        }
    
        // Filter rides by status
        if ($status) {
            $availableRides = array_filter($availableRides, function ($ride) use ($status) {
                return $ride->getStatus()->value === $status;
            });
        }
    
        // Sort requests and rides if sort parameter is provided
        $sortDir = $request->query->get('direction', 'asc');
        if ($sort) {
            // Sort requests based on the sort parameter
            usort($availableRequests, function ($a, $b) use ($sort, $sortDir) {
                $comparison = 0;
                
                switch ($sort) {
                    case 'date':
                        $comparison = $a->getRequestDate() <=> $b->getRequestDate();
                        break;
                    case 'name':
                        $comparison = $a->getUser()->getName() <=> $b->getUser()->getName();
                        break;
                    case 'status':
                        $comparison = $a->getStatus()->value <=> $b->getStatus()->value;
                        break;
                    default:
                        $comparison = $a->getRequestDate() <=> $b->getRequestDate();
                }
                
                return $sortDir === 'desc' ? -$comparison : $comparison;
            });
    
            // Sort rides based on the sort parameter
            if (!empty($availableRides)) {
                usort($availableRides, function ($a, $b) use ($sort, $sortDir) {
                    $comparison = 0;
                    
                    switch ($sort) {
                        case 'date':
                            $comparison = $a->getRequest()->getRequestDate() <=> $b->getRequest()->getRequestDate();
                            break;
                        case 'name':
                            $comparison = $a->getRequest()->getUser()->getName() <=> $b->getRequest()->getUser()->getName();
                            break;
                        case 'status':
                            $comparison = $a->getStatus()->value <=> $b->getStatus()->value;
                            break;
                        case 'price':
                            $comparison = $a->getPrice() <=> $b->getPrice();
                            break;
                        case 'distance':
                            $comparison = $a->getDistance() <=> $b->getDistance();
                            break;
                        default:
                            $comparison = $a->getRequest()->getRequestDate() <=> $b->getRequest()->getRequestDate();
                    }
                    
                    return $sortDir === 'desc' ? -$comparison : $comparison;
                });
            }
        }
    
        // Paginate requests using Pagerfanta
        $requestPaginator = new \Pagerfanta\Pagerfanta(new \Pagerfanta\Adapter\ArrayAdapter($availableRequests));
        $requestPaginator->setMaxPerPage(2);
        $requestPaginator->setCurrentPage($request->query->getInt('page_requests', 1));  // Get 'page_requests' from the URL, default to 1
    
        // Paginate rides using Pagerfanta
        $ridePaginator = new \Pagerfanta\Pagerfanta(new \Pagerfanta\Adapter\ArrayAdapter($availableRides));
        $ridePaginator->setMaxPerPage(2);
        $ridePaginator->setCurrentPage($request->query->getInt('page_rides', 1));  // Get 'page_rides' from the URL, default to 1
        
        // If 'export' is set in the query, return the Excel export
        if ($request->query->get('export')) {
            return $this->exportExcel($request);
        }
      
    
        // Map paginated data to include additional details
        $ridesWithDetails = [];
        foreach ($ridePaginator->getCurrentPageResults() as $ride) {
            $ridesWithDetails[] = [
                'id' => $ride->getIdRide(),
                'pickupLocation' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getAddress() : 'Unknown',
                'duration' => $ride->getDuration(),
                'dropoffLocation' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getAddress() : 'Unknown',
                'price' => $ride->getPrice(),
                'status' => $ride->getStatus()->value,
                'distance' => $ride->getDistance(),
                'userName' => $ride->getRequest()->getUser()->getName(),
                'pickupLat' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getLatitude() : null,
                'pickupLng' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getLongitude() : null,
                'dropoffLat' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getLatitude() : null,
                'dropoffLng' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getLongitude() : null,
                'time' => $ride->getRequest()->getRequestDate() ? $ride->getRequest()->getRequestDate()->format('Y-m-d H:i:s') : 'Unknown',
            ];
        }
    
        $requestsWithDetails = [];
        foreach ($requestPaginator->getCurrentPageResults() as $request) {
            $requestsWithDetails[] = [
                'id' => $request->getIdRequest(),
                'pickupLocation' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Unknown',
                'dropoffLocation' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Unknown',
                'pickupLat' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLatitude() : null,
                'pickupLng' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLongitude() : null,
                'dropoffLat' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLatitude() : null,
                'dropoffLng' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLongitude() : null,
                'time' => $request->getRequestDate() ? $request->getRequestDate()->format('Y-m-d H:i:s') : 'Unknown',
                'status' => $request->getStatus() instanceof REQUEST_STATUS ? $request->getStatus()->value : 'Unknown',
                'userName' => $request->getUser() ? $request->getUser()->getName() : 'Unknown',
            ];
        }
    
        return $this->render('back-office/taxi/taxi-management.html.twig', [
            'availableRequests' => $requestsWithDetails,
            'availableRides' => $ridesWithDetails,
            'paginationRequests' => $requestPaginator,
            'paginationRides' => $ridePaginator,
            'search' => $search,  // Pass search parameter to the template
            'status' => $status,  // Pass status parameter to the template
            'sort' => $sort,      // Pass sort parameter to the template
            'direction' => $sortDir, // Pass sort direction to the template
        ]);
    }

    #[Route('/ride/delete/{id}', name: 'delete_ride_backoffice', methods: ['POST'])]
    public function deleteRide(int $id): JsonResponse
    {
        try {
            // Call the deleteRide method from the RideService
            $this->rideService->deleteRide($id);
    
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Ride deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/admin/taxi-management/export-excel', name: 'admin_taxi_export_excel')]
    public function exportExcel(HttpRequest $request): Response
{
    $availableRequests = $this->requestService->getRealyAllRequest();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header row
    $headers = ['Request ID', 'User', 'Pickup Location', 'Dropoff Location', 'Status', 'Date'];
    $sheet->fromArray($headers, null, 'A1');

    // Style the header row (A1:F1)
    $headerStyle = $sheet->getStyle('A1:F1');
    $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $headerStyle->getFill()->getStartColor()->setARGB('FFCCFFCC'); // Light green
    $headerStyle->getFont()->setBold(true); // Make font bold

    // Autofit column widths
    foreach (range('A', 'F') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Fill data rows
    $row = 2;
    foreach ($availableRequests as $requestEntity) {
        $sheet->fromArray([
            $requestEntity->getIdRequest(),
            $requestEntity->getUser()->getName(),
            $requestEntity->getDepartureLocation()?->getAddress() ?? 'N/A',
            $requestEntity->getArrivalLocation()?->getAddress() ?? 'N/A',
            $requestEntity->getStatus()?->value ?? 'Unknown',
            $requestEntity->getRequestDate()?->format('Y-m-d H:i:s') ?? 'Unknown',
        ], null, 'A' . $row++);
    }

    // Create Excel writer
    $writer = new Xlsx($spreadsheet);

    // Create StreamedResponse to download the file
    $response = new StreamedResponse(function () use ($writer) {
        $writer->save('php://output');
    });

    // Set headers for file download
    $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'taxi_requests.xlsx'
    );

    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
}

#[Route('/admin/taxi-management/export-excel-rides', name: 'admin_taxi_export_excel_rides')]
public function exportRidesExcel(HttpRequest $request): Response
{
    $availableRides = $this->rideService->getAllRides();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header row
    $headers = [
        'Ride ID', 'User', 'Pickup Location', 'Dropoff Location',
        'Price', 'Distance', 'Status', 'Date'
    ];
    $sheet->fromArray($headers, null, 'A1');

    // Style the header row
    $headerStyle = $sheet->getStyle('A1:H1');
    $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $headerStyle->getFill()->getStartColor()->setARGB('FFCCFFCC'); // Light green
    $headerStyle->getFont()->setBold(true); // Bold font

    // Autofit column widths
    foreach (range('A', 'H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Populate ride rows
    $row = 2;
    foreach ($availableRides as $ride) {
        $sheet->fromArray([
            $ride->getIdRide(),
            //$ride->getRequest()->getUser()->getName(),
            $ride->getRequest()->getDepartureLocation()?->getAddress() ?? 'N/A',
            $ride->getRequest()->getArrivalLocation()?->getAddress() ?? 'N/A',
            $ride->getPrice(),
            $ride->getDistance(),
            $ride->getStatus()?->value ?? 'Unknown',
            $ride->getRequest()->getRequestDate()?->format('Y-m-d H:i:s') ?? 'Unknown',
        ], null, 'A' . $row++);
    }

    // Create Excel writer
    $writer = new Xlsx($spreadsheet);

    // Stream the file as a download
    $response = new StreamedResponse(function () use ($writer) {
        $writer->save('php://output');
    });

    // Set response headers
    $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'taxi_rides.xlsx'
    );

    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
}

#[Route('/admin/taxi-management/export-pdf', name: 'admin_taxi_export_pdf')]
public function exportPdf(HttpRequest $request): Response
{
    // Récupérer les demandes disponibles
    $availableRequests = $this->requestService->getRealyAllRequest();

    // Initialiser Dompdf
    $pdf = $this->pdf;

    // Créer le contenu HTML pour le PDF
    $html = $this->generateHtmlForPdf($availableRequests);

    // Charger le contenu HTML dans Dompdf
    $pdf->loadHtml($html);

    // (Optionnel) Définir la taille du papier
    $pdf->setPaper('A4', 'portrait'); // Ou utiliser 'landscape'

    // Rendre le PDF (première passe)
    $pdf->render();

    // Retourner le PDF généré (forcer le téléchargement)
    return new Response(
        $pdf->output(),
        Response::HTTP_OK,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="taxi_requests.pdf"',
        ]
    );
}

// Générer le contenu HTML pour le PDF
private function generateHtmlForPdf(array $availableRequests): string
{
    $html = '<h1>Demandes de Taxi</h1>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">';
    $html .= '<thead><tr><th>#</th><th>Utilisateur</th><th>Lieu de Prise en Charge</th><th>Lieu de Dépose</th><th>Statut</th><th>Date</th></tr></thead>';
    $html .= '<tbody>';

    foreach ($availableRequests as $request) {
        $html .= '<tr>';
        $html .= '<td>' . $request->getIdRequest() . '</td>';
        $html .= '<td>' . $request->getUser()->getName() . '</td>';
        $html .= '<td>' . $request->getDepartureLocation()?->getAddress() . '</td>';
        $html .= '<td>' . $request->getArrivalLocation()?->getAddress() . '</td>';
        $html .= '<td>' . $request->getStatus()->value . '</td>';
        $html .= '<td>' . $request->getRequestDate()?->format('Y-m-d H:i:s') . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}


#[Route('/admin/taxi-management/export-pdf-ride', name: 'admin_taxi_export_pdf_ride')]
    public function exportPdfForRide(HttpRequest $request): Response
    {
        // Retrieve all available rides
        $availableRides = $this->rideService->getAllRides(); // Modify this based on how you're fetching rides

        // Initialize Dompdf
        $pdf = $this->pdf;

        // Create HTML content for the PDF
        $html = $this->generateHtmlForPdfRides($availableRides);

        // Load HTML content into Dompdf
        $pdf->loadHtml($html);

        // (Optional) Set paper size (A4, portrait)
        $pdf->setPaper('A4', 'portrait'); 

        // Render the PDF (first pass)
        $pdf->render();

        // Output the generated PDF (force download)
        return new Response(
            $pdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="taxi_rides.pdf"',
            ]
        );
    }

    // Generate HTML content for the PDF (for the rides table)
    private function generateHtmlForPdfRides(array $availableRides): string
    {
        $html = '<h1>Taxi Rides</h1>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">';
        $html .= '<thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Ride Date</th>
                        <th>Pickup Location</th>
                        <th>Dropoff Location</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Distance (km)</th>
                    </tr>
                  </thead>';
        $html .= '<tbody>';
    
        foreach ($availableRides as $ride) {
            $html .= '<tr>';
            $html .= '<td>' . $ride->getIdRide() . '</td>';
            $html .= '<td>' ."badereddine derbel". '</td>';
            $html .= '<td>' . $ride->getRideDate()?->format('Y-m-d H:i:s') . '</td>';
            $html .= '<td>' . $ride->getRequest()?->getDepartureLocation()?->getAddress() . '</td>';
            $html .= '<td>' . $ride->getRequest()?->getArrivalLocation()?->getAddress() . '</td>';
            $html .= '<td>' . $ride->getRequest()?->getRequestDate()?->format('Y-m-d H:i:s') . '</td>';
            $html .= '<td>' . $ride->getStatus()?->value . '</td>';
            $html .= '<td>' . number_format($ride->getPrice(), 2) . '</td>';
            $html .= '<td>' . number_format($ride->getDistance(), 2) . '</td>';
            $html .= '</tr>';
        }
    
        $html .= '</tbody>';
        $html .= '</table>';
    
        return $html;
    }
}
