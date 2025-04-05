<?php

namespace App\Controller\front\bicycle;

use App\Entity\BicycleRental;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/services/bicycle')]
class BicycleVerificationController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/unlock-form', name: 'app_bicycle_unlock_form')]
    public function unlockForm(): Response
    {
        return $this->render('front/bicycle/unlock-form.html.twig');
    }

    #[Route('/verify-code', name: 'app_bicycle_verify_code', methods: ['GET', 'POST'])]
    public function verifyCode(Request $request): Response
    {
        // Get code from the form
        $code = $request->isMethod('POST')
            ? $request->request->get('code')
            : $request->query->get('code');

        // Extract rental ID from code (assuming format B00001)
        $rentalId = null;
        if (preg_match('/^B(\d+)$/', $code, $matches)) {
            $rentalId = (int) $matches[1];
        }

        if (!$rentalId) {
            $this->addFlash('error', 'Invalid code format. The code should start with B followed by numbers.');
            return $this->redirectToRoute('app_bicycle_unlock_form');
        }

        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($rentalId);
        $bicycle = null;

        if (!$rental) {
            $this->addFlash('error', 'Reservation not found. Please check the code and try again.');
            return $this->redirectToRoute('app_bicycle_unlock_form');
        } elseif ($rental->getEndTime() !== null) {
            $this->addFlash('error', 'This reservation has already ended.');
            return $this->redirectToRoute('app_bicycle_unlock_form');
        } else {
            // Simulate successful unlock by setting start time
            if ($rental->getStartTime() === null) {
                $rental->setStartTime(new \DateTime());
                $this->entityManager->flush();
            }

            // Get the bicycle for display
            $bicycle = $rental->getBicycle();

            // Success will be displayed on the unlock success page
            return $this->render('front/bicycle/unlock-success.html.twig', [
                'rental' => $rental,
                'bicycle' => $bicycle,
                'code' => $code
            ]);
        }
    }

    #[Route('/direct-unlock', name: 'app_bicycle_direct_unlock', methods: ['POST'])]
    public function directUnlock(Request $request): Response
    {
        // Get code directly from the form
        $code = $request->request->get('code');

        // Extract rental ID from code (assuming format B00001)
        $rentalId = null;
        if (preg_match('/^B(\d+)$/', $code, $matches)) {
            $rentalId = (int) $matches[1];
        }

        if (!$rentalId) {
            $this->addFlash('error', 'Invalid code format. The code should start with B followed by numbers.');
            return $this->redirectToRoute('app_bicycle_unlock_form');
        }

        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($rentalId);
        $bicycle = null;

        if (!$rental) {
            $this->addFlash('error', 'Reservation not found. Please check the code and try again.');
            return $this->redirectToRoute('app_bicycle_unlock_form');
        } elseif ($rental->getEndTime() !== null) {
            $this->addFlash('error', 'This reservation has already ended.');
            return $this->redirectToRoute('app_bicycle_unlock_form');
        } else {
            // Simulate successful unlock by setting start time
            if ($rental->getStartTime() === null) {
                $rental->setStartTime(new \DateTime());
                $this->entityManager->flush();
            }

            // Get the bicycle for display
            $bicycle = $rental->getBicycle();

            // Success will be displayed on the unlock success page
            return $this->render('front/bicycle/unlock-success.html.twig', [
                'rental' => $rental,
                'bicycle' => $bicycle,
                'code' => $code
            ]);
        }
    }
}
