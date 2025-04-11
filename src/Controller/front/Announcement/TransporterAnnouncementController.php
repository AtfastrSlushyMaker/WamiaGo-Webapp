<?php

namespace App\Controller\front\Announcement;

use App\Entity\Announcement;
use App\Entity\Driver;
use App\Enum\Zone;
use App\Form\TransporterAnnouncementType; 
use App\Repository\AnnouncementRepository;
use App\Repository\DriverRepository;
use App\Service\AnnouncementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\DataTransformer\ZoneTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotNull;

#[Route('/transporter/announcements')]
class TransporterAnnouncementController extends AbstractController
{
    private const HARDCODED_DRIVER_ID = 6;

    public function __construct(
        private readonly AnnouncementService $announcementService,
        private readonly DriverRepository $driverRepository
    ) {
    }

    /*#[Route('/new', name: 'app_transporter_announcement_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
        if (!$driver) {
            throw $this->createNotFoundException('Driver not found');
        }
    
        $announcement = new Announcement();
        $announcement->setDriver($driver);
        $announcement->setStatus(true);
        
        // Créez simplement le formulaire - le transformer est déjà dans le FormType
        $form = $this->createForm(TransporterAnnouncementType::class, $announcement);
        
        try {
            $form->handleRequest($request);
            
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->announcementService->createAnnouncement(
                        $driver,
                        $announcement->getTitle(),
                        $announcement->getContent(),
                        $announcement->getZone(),
                        new \DateTime(),
                        $announcement->isStatus()
                    );

                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Annonce créée avec succès!',
                            'redirectUrl' => $this->generateUrl('app_transporter_announcement_list')
                        ]);
                    }

                    $this->addFlash('success', 'Annonce créée avec succès!');
                    return $this->redirectToRoute('app_transporter_announcement_list');
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $this->getFormErrors($form)
                    ], 400);
                }
            }
        } catch (\Exception $e) {
            error_log('Form error: ' . $e->getMessage());
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'errors' => ['Erreur système: ' . $e->getMessage()]
                ], 500);
            }
            
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
        }

        return $this->render('front/announcement/transporter/add.html.twig', [
            'form' => $form->createView(),
            'zones' => Zone::cases()
        ]);
    }*/

    #[Route('/', name: 'app_transporter_announcement_list', methods: ['GET'])]
    public function list(): Response
    {
        $driver = $this->driverRepository->find(self::HARDCODED_DRIVER_ID);
        if (!$driver) {
            throw $this->createNotFoundException('Driver not found');
        }

        $announcements = $this->announcementService->getAnnouncementsByDriver($driver);

        return $this->render('front/announcement/transporter/list.html.twig', [
            'announcements' => $announcements
        ]);
    }

    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}