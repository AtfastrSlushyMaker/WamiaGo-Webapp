<?php

namespace App\Controller\front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Form\ProfileEditType;

class FrontOfficeController extends AbstractController
{
    #[Route('/', name: 'app_front_home')]
    public function index(): Response
    {
        return $this->render('front/index.html.twig', [
            'title' => 'Welcome to WamiaGo',
            'meta_description' => 'WamiaGo provides ride sharing, bicycle rentals, and transportation news for your community.'
        ]);
    }

    #[Route('/about', name: 'app_front_about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig', [
            'title' => 'About Us',
            'meta_description' => 'Learn about WamiaGo\'s mission, vision, and the team behind our transportation solutions.'
        ]);
    }

    #[Route('/contact', name: 'app_front_contact')]
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig', [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with WamiaGo for inquiries, support, or feedback.'
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/userProfile.html.twig', [
            'user' => $user,
            'profileCompletion' => $this->calculateProfileCompletion($user),
            'title' => 'My Profile',
            'meta_description' => 'View and manage your WamiaGo profile information.'
        ]);
    }

    private function calculateProfileCompletion($user): int
    {
        $completion = 0;
        $totalFields = 7; // Total number of fields to check

        if ($user->getName()) $completion++;
        if ($user->getEmail()) $completion++;
        if ($user->getPhoneNumber()) $completion++;
        if ($user->getLocation()) $completion++;
        if ($user->getDateOfBirth()) $completion++;
        if ($user->getProfilePicture()) $completion++;
        if ($user->isVerified()) $completion++;

        return (int) (($completion / $totalFields) * 100);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile picture upload
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                // Delete old profile picture if exists
                if ($user->getProfilePicture()) {
                    $oldPicturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getProfilePicture();
                    if (file_exists($oldPicturePath)) {
                        unlink($oldPicturePath);
                    }
                }

                // Generate a unique filename
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();

                try {
                    // Move the file to the uploads directory
                    $profilePictureFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );

                    // Update the user's profile picture
                    $user->setProfilePicture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading your profile picture. Please try again.');
                    return $this->redirectToRoute('app_profile_edit');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Your profile has been updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('front/profile_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'title' => 'Edit Profile',
            'meta_description' => 'Update your WamiaGo profile information.'
        ]);
    }
}
