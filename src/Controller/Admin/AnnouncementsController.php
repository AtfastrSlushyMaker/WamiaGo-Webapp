<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/announcements')]
class AnnouncementsController extends AbstractController
{
    #[Route('/', name: 'admin_announcements_index', methods: ['GET'])]
    public function index(AnnouncementRepository $announcementRepo): Response
    {
        return $this->render('back-office/Announcements/index.html.twig', [
            'announcements' => $announcementRepo->findBy([], ['date' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'admin_announcements_show', methods: ['GET'])]
    public function show(Announcement $announcement): Response
    {
        return $this->render('back-office/Announcements/show.html.twig', [
            'announcement' => $announcement,
        ]);
    }

    #[Route('/{id}', name: 'admin_announcements_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Announcement $announcement,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$announcement->getIdAnnouncement(), $request->request->get('_token'))) {
            $em->remove($announcement);
            $em->flush();
            $this->addFlash('success', 'Annonce supprimée avec succès');
        }

        return $this->redirectToRoute('admin_announcements_index');
    }
}