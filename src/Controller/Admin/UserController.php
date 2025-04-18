<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    private $userRepository;
    private $entityManager;
    private $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/test', name: 'admin_users_test', methods: ['GET'])]
    public function test(): Response
    {
        $hardcodedJson = '{"success":true,"users":[{"id_user":1,"name":"Test User","email":"test@example.com"}]}';
        return new Response($hardcodedJson, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/simple', name: 'admin_users_simple', methods: ['GET'])]
    public function simple(): Response
    {
        $hardcodedJson = '{"success":true,"users":[{"id_user":1,"name":"Test User","email":"test@example.com"}]}';
        return new Response($hardcodedJson, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', name: 'admin_users_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        try {
            // Debug authentication
            $user = $this->getUser();
            error_log('Current user: ' . ($user ? $user->getEmail() : 'not authenticated'));
            error_log('User roles: ' . ($user ? implode(', ', $user->getRoles()) : 'no roles'));
            
            if (!$this->isGranted('ROLE_ADMIN')) {
                error_log('Access denied - user does not have ROLE_ADMIN');
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }

            $users = $this->userRepository->findAll();
            $usersArray = [];
            
            foreach ($users as $user) {
                try {
                    $userData = [
                        'id_user' => $user->getId_user(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'phone_number' => $user->getPhoneNumber(),
                        'role' => $user->getRole()?->value,
                        'account_status' => $user->getAccountStatus()?->value,
                        'is_verified' => $user->isVerified(),
                        'profile_picture' => $user->getProfilePicture(),
                        'gender' => $user->getGender()?->value,
                        'date_of_birth' => $user->getDateOfBirth() ? $user->getDateOfBirth()->format('Y-m-d') : null,
                        'location' => $user->getLocation() ? $user->getLocation()->getAddress() : null,
                    ];
                    
                    // Convert any objects to strings and handle null values
                    array_walk_recursive($userData, function(&$item) {
                        if (is_object($item)) {
                            $item = (string) $item;
                        } elseif (is_null($item)) {
                            $item = '';
                        }
                    });
                    
                    $usersArray[] = $userData;
                } catch (\Exception $e) {
                    error_log('Error processing user: ' . $e->getMessage());
                    continue;
                }
            }

            $responseData = [
                'success' => true,
                'users' => $usersArray
            ];

            // Debug the response data
            error_log('Response data before encoding: ' . print_r($responseData, true));
            
            // Ensure clean output buffer
            if (ob_get_length()) ob_clean();
            
            // Create response with explicit content type and charset
            $response = new JsonResponse($responseData);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            
            // Debug the final response
            error_log('Final response headers: ' . print_r($response->headers->all(), true));
            
            return $response;
            
        } catch (\Exception $e) {
            error_log('Error in list method: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Ensure clean output buffer
            if (ob_get_length()) ob_clean();
            
            $errorResponse = new JsonResponse([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
            $errorResponse->headers->set('Content-Type', 'application/json; charset=utf-8');
            
            return $errorResponse;
        }
    }

    #[Route('/new', name: 'admin_users_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied.');
        }

        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user, [
            'require_password' => true,
        ]);

        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isValid()) {
            try {
                // Hash the password
                if ($plainPassword = $form->get('plainPassword')->getData()) {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user' => [
                        'id_user' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                    ]
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error creating user: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Invalid form data',
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'admin_users_edit', methods: ['POST'])]
    public function edit(Request $request, User $user): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied.');
        }

        $form = $this->createForm(AdminUserType::class, $user, [
            'require_password' => false,
        ]);

        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isValid()) {
            try {
                // Update password if provided
                if ($plainPassword = $form->get('plainPassword')->getData()) {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $this->entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'user' => [
                        'id_user' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                    ]
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error updating user: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Invalid form data',
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied.');
        }

        try {
            // Delete profile picture if exists
            if ($user->getProfilePicture()) {
                $picturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getProfilePicture();
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 