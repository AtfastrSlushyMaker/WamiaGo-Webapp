<?php

namespace App\Controller\front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Form\ProfileEditType;
use App\Entity\User;
use Symfony\Component\HttpKernel\Kernel;

class FrontOfficeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/', name: 'app_front_home')]
    public function index(): Response
    {
        return $this->render('front/index.html.twig', [
            'title' => 'Welcome to WamiaGo',
            'meta_description' => 'WamiaGo provides ride sharing, bicycle rentals, and transportation news for your community.',
            'app_environment' => $_ENV['APP_ENV'] ?? 'dev',
            'app_version' => Kernel::VERSION,
        ]);
    }   
    #[Route('/system-check', name: 'app_system_check')]
    public function systemCheck(): Response
    {
        $systemInfo = [
            'status' => 'ok',
            'php_version' => PHP_VERSION,
            'symfony_version' => Kernel::VERSION,
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'debug' => $_ENV['APP_DEBUG'] ?? false,
            'front_controller' => 'index.php',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        
        return $this->json($systemInfo);
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
    }    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $profileCompletion = $this->calculateProfileCompletion($user);

            return $this->render('front/userProfile.html.twig', [
                'user' => $user,
                'profileCompletion' => $profileCompletion,
                'title' => 'My Profile',
                'meta_description' => 'View and manage your WamiaGo profile information.'
            ]);
        } catch (\Exception $e) {
            // Log the error
            if ($this->container->has('logger')) {
                $this->container->get('logger')->error('Profile rendering error: ' . $e->getMessage());
            }
            
            $this->addFlash('error', 'There was an error loading your profile. Please try again later.');
            return $this->redirectToRoute('app_front_home');
        }
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
    }    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $profilePictureFile = $form->get('profilePicture')->getData();
                if ($profilePictureFile) {
                    if ($user->getProfilePicture()) {
                        $oldPicturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getProfilePicture();
                        if (file_exists($oldPicturePath)) {
                            unlink($oldPicturePath);
                        }
                    }
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
    
                $this->entityManager->flush();
    
                $this->addFlash('success', 'Your profile has been updated successfully!');
                return $this->redirectToRoute('app_profile');
            } catch (\Exception $e) {
                if ($this->container->has('logger')) {
                    $this->container->get('logger')->error('Profile update error: ' . $e->getMessage());
                }
                
                $this->addFlash('error', 'An error occurred while updating your profile. Please try again.');
                return $this->redirectToRoute('app_profile_edit');
            }
        }

        return $this->render('front/profile_edit.html.twig', [
            'form' => $form->createView(),            'user' => $user,
            'title' => 'Edit Profile',
            'meta_description' => 'Update your WamiaGo profile information.'
        ]);
    }
    
    /**
     * Check if the front controller is functioning correctly
     */
    #[Route('/front-controller-check', name: 'app_front_controller_check')]
    public function frontControllerCheck(): Response
    {
        $indexPhpPath = dirname(__DIR__, 3) . '/public/index.php';
        
        $checkResult = [
            'exists' => file_exists($indexPhpPath),
            'readable' => is_readable($indexPhpPath),
            'size' => file_exists($indexPhpPath) ? filesize($indexPhpPath) : 0,
            'permissions' => file_exists($indexPhpPath) ? substr(sprintf('%o', fileperms($indexPhpPath)), -4) : 'unknown',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
            'php_self' => $_SERVER['PHP_SELF'] ?? 'unknown',
        ];
        
        return $this->json($checkResult);
    }
}
