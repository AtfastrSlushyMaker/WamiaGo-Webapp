<?php

namespace App\Controller\front;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new reclamation
        $reclamation = new Reclamation();
        
        // Try to get user with ID 1 or create a temporary user if needed
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find(1);
        
        // If user doesn't exist, we'll create one temporarily or use null
        if (!$user) {
            // For demonstration/development only - in production, you'd want to use a real user
            // This is to prevent failures due to missing user association
            $user = new User();
            // Set minimum required fields for a valid user
            $user->setEmail('demo@wamiango.com');
            $user->setName('Demo User');
            $user->setPassword('password123'); // Not secure, just for demo
            $user->setPhoneNumber('1234567890');
            $user->setRole('ROLE_USER');
            $user->setGender('Unspecified');
            $user->setStatus('ACTIVE');
            $user->setAccountStatus('ACTIVE');
            $user->setIsVerified(true);
            
            // Persist this temporary user
            $entityManager->persist($user);
            $entityManager->flush();
        }
        
        // Set the user (which now should exist)
        $reclamation->setUser($user);
        
        // Set initial values
        $reclamation->setDate(new \DateTime());
        $reclamation->setStatus(false);
        
        // Create the form
        $form = $this->createForm(ReclamationType::class, $reclamation);
        
        // Handle form submission
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Ensure we have the necessary fields set
                if (!$reclamation->getTitle() || !$reclamation->getContent()) {
                    throw new \Exception('Title and content are required');
                }
                
                // Double check date is set
                if (!$reclamation->getDate()) {
                    $reclamation->setDate(new \DateTime());
                }
                
                // Double check status is set
                if ($reclamation->isStatus() === null) {
                    $reclamation->setStatus(false);
                }
                
                // Verify CAPTCHA (handled automatically by the form validation)
                // CAPTCHA is validated by the bundle as part of form validation
                
                // Debug information - showing this to help troubleshoot
                $this->addFlash('info', 'Debug: Title: ' . $reclamation->getTitle() . 
                    ' | Content: ' . substr($reclamation->getContent(), 0, 20) . '...' .
                    ' | User ID: ' . ($reclamation->getUser() ? $reclamation->getUser()->getId_user() : 'null'));
                
                // Persist and save the reclamation
                $entityManager->persist($reclamation);
                $entityManager->flush();
                
                $this->addFlash('success', 'Your reclamation has been submitted successfully!');
                
                // Redirect to avoid form resubmission
                return $this->redirectToRoute('app_front_contact');
                
            } catch (\Exception $e) {
                // Log the error for debugging
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                // More detailed error info
                $this->addFlash('error', 'Error details: ' . get_class($e) . ' at line ' . $e->getLine());
            }
        } elseif ($form->isSubmitted()) {
            // If submitted but not valid, show form errors
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            
            // Check specifically for CAPTCHA errors
            if ($form->get('captcha')->getErrors()->count() > 0) {
                foreach ($form->get('captcha')->getErrors() as $error) {
                    $this->addFlash('error', 'CAPTCHA Error: ' . $error->getMessage());
                }
            }
            
            $this->addFlash('error', 'Please check your submission. There were validation errors.');
        }
        
        return $this->render('front/contact.html.twig', [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with WamiaGo for inquiries, support, or feedback.',
            'form' => $form->createView(),
        ]);
    }    
    #[Route('/profile/edit', name: 'app_profile_edit')]
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
}
