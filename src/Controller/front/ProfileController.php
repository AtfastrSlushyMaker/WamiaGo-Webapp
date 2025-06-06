<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Entity\Location;
use App\Form\AvatarUploadType;
use App\Form\ProfileEditType;
use App\Form\ChangePasswordType;
use App\Service\CloudinaryService;
use App\Service\SecurityNotificationService;
use App\Service\TwoFactorSessionHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile', name: 'app_profile_')]
class ProfileController extends AbstractController
{
    private SecurityNotificationService $notificationService;
    private TwoFactorSessionHandler $twoFactorSessionHandler;

    public function __construct(
        SecurityNotificationService $notificationService,
        TwoFactorSessionHandler $twoFactorSessionHandler
    ) {
        $this->notificationService = $notificationService;
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // Check if 2FA is enabled
        $twoFactorEnabled = $this->twoFactorSessionHandler->is2faEnabled();
        
        return $this->render('front/userProfile.html.twig', [
            'profileForm' => $this->createForm(ProfileEditType::class, $this->getUser())->createView(),
            'passwordForm' => $this->createForm(ChangePasswordType::class)->createView(),
            'twoFactorEnabled' => $twoFactorEnabled,
        ]);
    }

    #[Route('/change-avatar', name: 'change_avatar', methods: ['POST'])]
    public function changeAvatar(Request $request, EntityManagerInterface $entityManager, CloudinaryService $cloudinaryService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$request->files->has('avatar')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No file uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        $avatarFile = $request->files->get('avatar');
        
        // Validate the file
        $validMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($avatarFile->getMimeType(), $validMimeTypes)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid file type. Please upload a JPEG, PNG, or GIF image.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        if ($avatarFile->getSize() > $maxFileSize) {
            return new JsonResponse([
                'success' => false,
                'message' => 'File is too large. Maximum size is 2MB.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Generate a unique public ID for the image using user ID and timestamp
        $publicId = 'user_' . $user->getId_user() . '_' . time();
        
        try {
            // Upload the file to Cloudinary
            $uploadResult = $cloudinaryService->uploadImage($avatarFile, $publicId);
            
            if ($uploadResult === null || !isset($uploadResult['secure_url'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Failed to upload image to cloud storage'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            // Extract the secure URL for storage
            $imageUrl = $uploadResult['secure_url'];
            
            // Delete the old profile picture from Cloudinary if it exists
            $oldProfileUrl = $user->getProfilePicture();
            if ($oldProfileUrl && strpos($oldProfileUrl, 'cloudinary.com') !== false) {
                // Extract the public ID from the old URL
                preg_match('/\/v\d+\/(.+?)\./', $oldProfileUrl, $matches);
                if (isset($matches[1])) {
                    $oldPublicId = $matches[1];
                    // Delete the old image
                    $cloudinaryService->deleteImage($oldPublicId);
                }
            }
            
            // Update the user's profile picture URL
            $user->setProfilePicture($imageUrl);
            $entityManager->persist($user);
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'imageUrl' => $imageUrl
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        // Debug incoming request
        $requestContent = $request->getContent();
        $requestData = json_decode($requestContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid JSON data received',
                'debug' => [
                    'error' => json_last_error_msg(),
                    'content' => $requestContent
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->submit($requestData);

        if ($form->isValid()) {
            try {
                // Handle date of birth
                if (isset($requestData['date_of_birth'])) {
                    $dateOfBirth = \DateTime::createFromFormat('Y-m-d', $requestData['date_of_birth']);
                    if ($dateOfBirth) {
                        $user->setDateOfBirth($dateOfBirth);
                    }
                }

                $entityManager->persist($user);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'An error occurred while saving: ' . $e->getMessage(),
                    'debug' => [
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // If form is not valid, return the errors
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Invalid form data',
            'errors' => $errors,
            'debug' => [
                'submitted_data' => $requestData,
                'form_errors' => $errors
            ]
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/change-info', name: 'change_info', methods: ['POST'])]
    public function changeInfo(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'User not found. Please log in again.');
            return $this->redirectToRoute('app_login');
        }

        // Get form data
        $name = $request->request->get('name');
        $phoneNumber = $request->request->get('phoneNumber');
        $locationAddress = $request->request->get('location');
        $genderString = $request->request->get('gender');
        $dateOfBirthString = $request->request->get('dateOfBirth');

        // Update user info
        if ($name) {
            $user->setName($name);
        }
        
        if ($phoneNumber) {
            $user->setPhoneNumber($phoneNumber);
        }
        
        // Handle location (convert string to Location object)
        if ($locationAddress) {
            // Check if there's an existing location with this address
            $locationRepository = $entityManager->getRepository(Location::class);
            $location = $locationRepository->findOneBy(['address' => $locationAddress]);
            
            if (!$location) {
                // Create a new Location object if none exists
                $location = new Location();
                $location->setAddress($locationAddress);
                // Set default coordinates or use a geocoding service here if needed
                $location->setLatitude(0.0);
                $location->setLongitude(0.0);
                $entityManager->persist($location);
            }
            
            $user->setLocation($location);
        }
        
        // Handle gender (convert string to GENDER enum)
        if ($genderString) {
            try {
                $gender = \App\Enum\GENDER::from(strtoupper($genderString));
                $user->setGender($gender);
            } catch (\ValueError $e) {
                $this->addFlash('error', 'Invalid gender value. Please select either MALE or FEMALE.');
            }
        }
        
        if ($dateOfBirthString) {
            try {
                $dateOfBirth = new \DateTime($dateOfBirthString);
                $user->setDateOfBirth($dateOfBirth);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date format for date of birth.');
            }
        }

        try {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profile information updated successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while saving your profile information: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/check-password-strength', name: 'check_password_strength', methods: ['POST'])]
    public function checkPasswordStrength(Request $request): JsonResponse
    {
        $password = json_decode($request->getContent(), true)['password'] ?? '';
        
        if (empty($password)) {
            return new JsonResponse(['strength' => 0, 'message' => 'Password is empty']);
        }
        
        $score = 0;
        $feedback = [];
        
        // Length check
        if (strlen($password) >= 8) {
            $score += 20;
        } else {
            $feedback[] = 'Password should be at least 8 characters long';
        }
        
        // Uppercase letter check
        if (preg_match('/[A-Z]/', $password)) {
            $score += 20;
        } else {
            $feedback[] = 'Add uppercase letter(s)';
        }
        
        // Lowercase letter check
        if (preg_match('/[a-z]/', $password)) {
            $score += 20;
        } else {
            $feedback[] = 'Add lowercase letter(s)';
        }
        
        // Number check
        if (preg_match('/[0-9]/', $password)) {
            $score += 20;
        } else {
            $feedback[] = 'Add number(s)';
        }
        
        // Special character check
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 20;
        } else {
            $feedback[] = 'Add special character(s)';
        }
        
        // Determine strength level
        $level = 'weak';
        if ($score >= 80) {
            $level = 'strong';
        } elseif ($score >= 50) {
            $level = 'medium';
        }
        
        return new JsonResponse([
            'strength' => $score,
            'level' => $level,
            'feedback' => $feedback
        ]);
    }

    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        // Debug incoming request
        $requestContent = $request->getContent();
        $requestData = json_decode($requestContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid JSON data received',
                'debug' => [
                    'error' => json_last_error_msg(),
                    'content' => $requestContent
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        // Log received data for debugging
        error_log('Received change password data: ' . json_encode($requestData));

        $form = $this->createForm(ChangePasswordType::class);
        
        // Handle both nested and flat structures for backward compatibility
        $formData = $requestData['change_password'] ?? $requestData;
        
        $form->submit($formData);

        if ($form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], Response::HTTP_BAD_REQUEST);
            }

            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            
            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Send password change notification email
                $clientIp = $request->getClientIp() ?? 'unknown';
                $userAgent = $request->headers->get('User-Agent') ?? 'unknown';
                
                try {
                    $this->notificationService->sendPasswordChangeNotification($user, $clientIp, $userAgent);
                } catch (\Exception $notificationException) {
                    error_log('Error in notification service: ' . $notificationException->getMessage());
                }

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error saving password',
                    'debug' => [
                        'exception' => get_class($e),
                        'message' => $e->getMessage()
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // If form is not valid, return the form errors
        $errors = $this->getFormErrors($form);
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Invalid form data',
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }
    
    /**
     * Recursively extracts form errors
     */
    private function getFormErrors($form): array
    {
        $errors = [];
        
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof \Symfony\Component\Form\FormInterface) {
                $childErrors = $this->getFormErrors($childForm);
                if ($childErrors) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        
        return $errors;
    }
}