<?php

namespace App\Controller\front\Reservation;

use App\Entity\Reservation;
use App\Entity\Relocation;
use App\Enum\ReservationStatus;
use App\Form\RelocationType;
use App\Repository\ReservationRepository;
use App\Repository\DriverRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transporter/reservations')]
class TransporterReservationController extends AbstractController
{
    private const HARDCODED_DRIVER_ID = 6;

    #[Route('/', name: 'app_transporter_reservation_list', methods: ['GET'])]
    public function list(ReservationRepository $reservationRepo, DriverRepository $driverRepo): Response
    {
        $driver = $driverRepo->find(self::HARDCODED_DRIVER_ID);
        
        if (!$driver) {
            throw $this->createNotFoundException('Driver not found');
        }

        $reservations = $reservationRepo->findByDriver($driver);

        return $this->render('front/reservation/transporter/list.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/{id}/details', name: 'app_reservation_details', methods: ['GET'])]
    public function details(Reservation $reservation): JsonResponse
    {
        return $this->json([
            'title' => $reservation->getAnnouncement()->getTitle(),
            'description' => $reservation->getDescription(),
            'date' => $reservation->getDate()->format('d M Y, H:i'),
            'status' => $reservation->getStatus()->value,
            'startLocation' => $reservation->getStartLocation()->getAddress(),
            'endLocation' => $reservation->getEndLocation()->getAddress(),
            'client' => $reservation->getUser()->getName()
        ]);
    }

    #[Route('/{id}/accept', name: 'app_reservation_accept', methods: ['POST'])]
    public function accept(Request $request, Reservation $reservation, EntityManagerInterface $em): JsonResponse
    {
        if ($this->isCsrfTokenValid('accept'.$reservation->getIdReservation(), $request->request->get('_token'))) {
            $reservation->setStatus(ReservationStatus::CONFIRMED);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Reservation accepted successfully',
                'newStatus' => $reservation->getStatus()->value
            ]);
        }

        return $this->json(['error' => 'Invalid CSRF token'], 400);
    }

    #[Route('/{id}/refuse', name: 'app_reservation_refuse', methods: ['POST'])]
    public function refuse(Request $request, Reservation $reservation, EntityManagerInterface $em): JsonResponse
    {
        if ($this->isCsrfTokenValid('refuse'.$reservation->getIdReservation(), $request->request->get('_token'))) {
            $reservation->setStatus(ReservationStatus::CANCELLED);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Reservation refused successfully',
                'newStatus' => $reservation->getStatus()->value
            ]);
        }

        return $this->json(['error' => 'Invalid CSRF token'], 400);
    }
}