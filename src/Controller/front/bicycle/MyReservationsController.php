<?php

namespace App\Controller\front\bicycle;

use App\Entity\BicycleRental;
use App\Service\BicycleRentalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/my-reservations')]
class MyReservationsController extends AbstractController
{
    private $entityManager;
    private $rentalService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleRentalService $rentalService
    ) {
        $this->entityManager = $entityManager;
        $this->rentalService = $rentalService;
    }

    #[Route('/', name: 'app_front_my_reservations')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('User not found or invalid');
        }

        $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        $pastRentals = $this->rentalService->getPastRentalsForUser($user);

        return $this->render('front/my-reservations.html.twig', [
            'activeRentals' => $activeRentals,
            'pastRentals' => $pastRentals,
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_rental_cancel', methods: ['POST'])]
    public function cancelRental(int $id, Request $request): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);

        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
        } elseif ($rental->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You can only cancel your own reservations');
        } else {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('cancel' . $rental->getIdUserRental(), $token)) {
                $this->addFlash('error', 'Invalid CSRF token');
            } else {
                try {
                    $this->rentalService->cancelRental($rental);
                    $this->addFlash('success', 'Reservation cancelled successfully');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error cancelling reservation: ' . $e->getMessage());
                }
            }
        }

        return $this->redirectToRoute('app_front_my_reservations');
    }

    #[Route('/unlock-code/{id}', name: 'app_rental_unlock_code')]
    public function unlockCode(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);

        if (!$rental || $rental->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Reservation not found');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        return $this->render('front/unlock-code.html.twig', [
            'rental' => $rental
        ]);
    }
}
