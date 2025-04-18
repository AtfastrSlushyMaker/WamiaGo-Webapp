<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Enum\ROLE;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\GENDER;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UsersController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role');
            $status = $request->request->get('status');
            
            $user->setName($name);
            $user->setEmail($email);
            $user->setRole($role);
            $user->setAccountStatus($status);
            
            if ($password) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('admin_users_index');
        }
        
        return $this->render('admin/users/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role');
            $status = $request->request->get('status');
            
            $user->setName($name);
            $user->setEmail($email);
            $user->setRole($role);
            $user->setAccountStatus($status);
            
            if ($password) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('admin_users_index');
        }
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/api/list', name: 'admin_users_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        try {
            // Clean output buffer in case there's any previous output
            if (ob_get_length()) ob_clean();
            
            // Check authentication
            $user = $this->getUser();
            if (!$user || !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $users = $this->userRepository->findAll();
            $usersData = [];
            
            foreach ($users as $user) {
                try {
                    $usersData[] = [
                        'id' => $user->getId_user(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'role' => $user->getRole(),
                        'accountStatus' => $user->getAccountStatus() ? $user->getAccountStatus()->value : null,
                        'isVerified' => $user->isVerified(),
                        'profilePicture' => $user->getProfilePicture() ?: null
                    ];
                } catch (\Exception $e) {
                    // Log issues with individual users but continue processing
                    error_log('Error processing user: ' . $e->getMessage());
                    continue;
                }
            }
            
            $response = new JsonResponse([
                'success' => true,
                'users' => $usersData
            ]);
            
            // Set headers to prevent caching and ensure content type
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            
            return $response;
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Error in apiList: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Clean buffer in case there was an output
            if (ob_get_length()) ob_clean();
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/user/{id}', name: 'admin_users_api_user', methods: ['GET'])]
    public function apiUser(User $user): JsonResponse
    {
        try {
            if (ob_get_length()) ob_clean();
            
            // Check authentication
            $currentUser = $this->getUser();
            if (!$currentUser || !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $userData = [
                'id' => $user->getId_user(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'accountStatus' => $user->getAccountStatus() ? $user->getAccountStatus()->value : null,
                'isVerified' => $user->isVerified(),
                'profilePicture' => $user->getProfilePicture() ?: null
            ];
            
            $response = new JsonResponse([
                'success' => true,
                'user' => $userData
            ]);
            
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            
            return $response;
        } catch (\Exception $e) {
            error_log('Error in apiUser: ' . $e->getMessage());
            
            if (ob_get_length()) ob_clean();
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error fetching user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/new', name: 'admin_users_api_new', methods: ['POST'])]
    public function apiNew(Request $request): JsonResponse
    {
        try {
            if (ob_get_length()) ob_clean();
            
            // Check authentication
            $currentUser = $this->getUser();
            if (!$currentUser || !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $data = json_decode($request->getContent(), true);
            
            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            
            // Handle role
            $roleValue = $data['role'] ?? 'ROLE_USER';
            $role = ROLE::from($roleValue);
            $user->setRole($role);
            
            // Handle account status
            $statusValue = $data['accountStatus'] ?? 'active';
            $status = ACCOUNT_STATUS::from($statusValue);
            $user->setAccountStatus($status);
            
            // Set basic required fields (we may need more depending on your User entity)
            $user->setPhoneNumber('20000000'); // Default placeholder
            $user->setGender(GENDER::MALE); // Default placeholder
            
            if (!empty($data['password'])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $response = new JsonResponse([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()
                ]
            ]);
            
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        } catch (\Exception $e) {
            error_log('Error in apiNew: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            if (ob_get_length()) ob_clean();
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/edit/{id}', name: 'admin_users_api_edit', methods: ['POST'])]
    public function apiEdit(Request $request, User $user): JsonResponse
    {
        try {
            if (ob_get_length()) ob_clean();
            
            // Check authentication
            $currentUser = $this->getUser();
            if (!$currentUser || !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $data = json_decode($request->getContent(), true);
            
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            
            // Handle role (convert string to enum)
            if (isset($data['role'])) {
                $roleValue = $data['role'];
                $role = ROLE::from($roleValue);
                $user->setRole($role);
            }
            
            // Handle account status (convert string to enum)
            if (isset($data['accountStatus'])) {
                $statusValue = $data['accountStatus'];
                $status = ACCOUNT_STATUS::from($statusValue);
                $user->setAccountStatus($status);
            }
            
            if (!empty($data['password'])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->flush();
            
            $response = new JsonResponse([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'accountStatus' => $user->getAccountStatus() ? $user->getAccountStatus()->value : null
                ]
            ]);
            
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        } catch (\Exception $e) {
            error_log('Error in apiEdit: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            if (ob_get_length()) ob_clean();
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/delete/{id}', name: 'admin_users_api_delete', methods: ['POST'])]
    public function apiDelete(User $user): JsonResponse
    {
        try {
            if (ob_get_length()) ob_clean();
            
            // Check authentication
            $currentUser = $this->getUser();
            if (!$currentUser || !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Don't allow deleting self
            if ($currentUser->getId_user() === $user->getId_user()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            
            $response = new JsonResponse([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        } catch (\Exception $e) {
            error_log('Error in apiDelete: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            if (ob_get_length()) ob_clean();
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/test', name: 'admin_users_api_test', methods: ['GET'])]
    public function apiTest(): JsonResponse
    {
        // Clean any output buffer
        if (ob_get_length()) ob_clean();
        
        // Create a simple response with no database operations
        $response = new JsonResponse([
            'success' => true,
            'message' => 'API test successful',
            'timestamp' => new \DateTime(),
            'user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'not authenticated'
        ]);
        
        // Set explicit headers
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

    #[Route('/apitest', name: 'admin_users_apitest', methods: ['GET'])]
    public function apiTestPage(): Response
    {
        return $this->render('admin/users/apitest.html.twig');
    }
} 