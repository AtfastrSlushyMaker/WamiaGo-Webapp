<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Form\AvatarUploadType;
use App\Form\ProfileEditType;
use App\Form\ChangePasswordType;
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
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('front/userProfile.html.twig', [
            'profileForm' => $this->createForm(ProfileEditType::class, $this->getUser())->createView(),
            'passwordForm' => $this->createForm(ChangePasswordType::class)->createView(),
        ]);
    }

    #[Route('/change-avatar', name: 'change_avatar', methods: ['POST'])]
    public function changeAvatar(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $form = $this->createForm(AvatarUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();

                try {
                    $avatarFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );

                    // Delete old profile picture if exists
                    if ($user->getProfilePicture() && file_exists($this->getParameter('profile_pictures_directory').'/'.$user->getProfilePicture())) {
                        unlink($this->getParameter('profile_pictures_directory').'/'.$user->getProfilePicture());
                    }

                    $user->setProfilePicture($newFilename);
                    $entityManager->persist($user);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Profile picture updated successfully',
                        'newFilename' => $newFilename
                    ]);
                } catch (\Exception $e) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Error uploading file: ' . $e->getMessage()
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Invalid file upload'
        ], Response::HTTP_BAD_REQUEST);
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

        $form = $this->createForm(ChangePasswordType::class);
        $form->submit($requestData);

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
            'message' => 'Invalid form submission',
            'errors' => $errors,
            'debug' => [
                'submitted_data' => $requestData,
                'form_errors' => $errors
            ]
        ], Response::HTTP_BAD_REQUEST);
    }
} 