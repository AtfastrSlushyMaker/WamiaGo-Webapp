<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Enum\ROLE;
use App\Enum\GENDER;
use App\Enum\ACCOUNT_STATUS;

class UserController extends AbstractController
{
    private UserService $userService;
    private SerializerInterface $serializer;
    private $logger;

    public function __construct(
        UserService $userService, 
        SerializerInterface $serializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->userService = $userService;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    #[Route('/admin/users', name: 'admin_users_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        try {
            $query = $this->userService->getUserQuery();
            
            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                10 // Items per page
            );

            return $this->render('admin/users/index.html.twig', [
                'pagination' => $pagination
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to fetch users: ' . $e->getMessage());
            return $this->render('admin/users/index.html.twig', [
                'pagination' => []
            ]);
        }
    }

    #[Route('/admin/users/api', name: 'admin_users_api', methods: ['GET'])]
    public function api(Request $request, PaginatorInterface $paginator): JsonResponse
    {
        try {
            // Get filter parameters from request
            $filters = [
                'search' => $request->query->get('search'),
                'role' => $request->query->get('role'),
                'status' => $request->query->get('status'),
                'verified' => $request->query->get('verified'),
                'orderBy' => $request->query->get('orderBy'),
                'orderDirection' => $request->query->get('orderDirection')
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            // Get filtered query from service
            $query = $this->userService->getUserQuery($filters);
            
            $itemsPerPage = $request->query->getInt('items', 10);
            $itemsPerPage = min(max($itemsPerPage, 5), 100); // Limit between 5 and 100
            
            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                $itemsPerPage
            );            $users = [];
            foreach ($pagination as $user) {
                $users[] = [
                    'id_user' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'phone_number' => $user->getPhone_number(),
                    'role' => $user->getRole(),
                    'account_status' => $user->getAccount_status(),
                    'gender' => $user->getGender(),
                    'is_verified' => $user->is_verified(),
                    'profile_picture' => $user->getProfilePicture() ? $user->getProfilePicture() : '/images/default-avatar.png',
                    'date_of_birth' => $user->getDate_of_birth() ? $user->getDate_of_birth()->format('Y-m-d') : null,
                    'status' => $user->getStatus()
                ];
            }            return new JsonResponse([
                'users' => $users,
                'total' => $pagination->getTotalItemCount(),
                'page' => $pagination->getCurrentPageNumber(),
                'pages' => ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()),
                'itemsPerPage' => $itemsPerPage,
                'filters' => [
                    'search' => $request->query->get('search') ?? '',
                    'role' => $request->query->get('role') ?? '',
                    'status' => $request->query->get('status') ?? '',
                    'verified' => $request->query->get('verified') ?? ''
                ]
            ]);        } catch (\Exception $e) {
            // Log the error with more details
            $this->logger->error('Failed to fetch users: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return new JsonResponse([
                'error' => 'Failed to fetch users: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    #[Route('/admin/users/generate-test', name: 'admin_users_generate_test', methods: ['GET'])]
    public function generateTestUsers(): Response
    {
        try {
            // Generate 5 test users
            for ($i = 1; $i <= 5; $i++) {
                $userData = [
                    'name' => 'Test User ' . $i,
                    'email' => 'testuser' . $i . '@example.com',
                    'password' => 'password123',
                    'phone_number' => '55123456',
                    'role' => ROLE::CLIENT,
                    'gender' => GENDER::MALE,
                    'account_status' => ACCOUNT_STATUS::ACTIVE,
                    'date_of_birth' => new \DateTime('1990-01-01')
                ];

                $this->userService->createUser($userData);
            }

            $this->addFlash('success', '5 test users created successfully');
            return $this->redirectToRoute('admin_users_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error creating test users: ' . $e->getMessage());
            return $this->redirectToRoute('admin_users_index');
        }
    }

    #[Route('/admin/users/new', name: 'admin_users_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('admin/users/new.html.twig');
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_users_edit', methods: ['GET'])]
    public function edit(User $user): Response
    {
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/users', name: 'api_users', methods: ['GET'])]
    public function apiIndex(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers();
            $serializedUsers = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);

            return new JsonResponse(
                $serializedUsers,
                200,
                ['Content-Type' => 'application/json'],
                true
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to fetch users: ' . $e->getMessage()],
                500,
                ['Content-Type' => 'application/json']
            );
        }
    }

    #[Route('/users', name: 'api_user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Invalid JSON payload'],
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $user = $this->userService->createUser($data);

            return new JsonResponse(
                $this->serializer->serialize($user, 'json', ['groups' => 'user:read']),
                201,
                ['Content-Type' => 'application/json'],
                true
            );

        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                400,
                ['Content-Type' => 'application/json']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Server error: ' . $e->getMessage()],
                500,
                ['Content-Type' => 'application/json']
            );
        }
    }

    #[Route('/users/{id}', name: 'api_user_update', methods: ['PUT'])]
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Invalid JSON payload'],
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $updatedUser = $this->userService->updateUser($user, $data);

            return new JsonResponse(
                $this->serializer->serialize($updatedUser, 'json', ['groups' => 'user:read']),
                200,
                ['Content-Type' => 'application/json'],
                true
            );

        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                400,
                ['Content-Type' => 'application/json']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Server error: ' . $e->getMessage()],
                500,
                ['Content-Type' => 'application/json']
            );
        }
    }

    #[Route('/users/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            return $this->json(
                ['message' => 'User deleted successfully'],
                200,
                ['Content-Type' => 'application/json']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Server error: ' . $e->getMessage()],
                500,
                ['Content-Type' => 'application/json']
            );
        }
    }

    #[Route('/admin/users/delete/api/{id}', name: 'admin_delete_user_api', methods: ['POST'])]
    public function deleteUserApi(int $id): JsonResponse
    {
        try {
            // Find the user
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Delete the user
            $this->userService->deleteUser($user);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/users/add', name: 'admin_add_user_api', methods: ['POST'])]
    public function addUserApi(Request $request): JsonResponse
    {
        try {
            // Get form data
            $formData = [
                'name' => $request->request->get('name'),
                'email' => $request->request->get('email'),
                'password' => $request->request->get('password'),
                'phone_number' => $request->request->get('phone_number'),
                'role' => $request->request->get('role'),
                'gender' => $request->request->get('gender'),
                'account_status' => $request->request->get('account_status'),
                'date_of_birth' => $request->request->get('date_of_birth'),
                'profilePicture' => $request->request->get('profilePicture'),
                'is_verified' => $request->request->getBoolean('is_verified')
            ];
            
            // Create the user
            $user = $this->userService->createUser($formData);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id_user' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage(),
                'errors' => ['general' => $e->getMessage()]
            ], 500);
        }
    }
    
    #[Route('/admin/users/update', name: 'admin_update_user_api', methods: ['POST'])]
    public function updateUserApi(Request $request): JsonResponse
    {
        try {
            // Get user ID from request
            $userId = $request->request->getInt('id_user');
            
            // Find the user
            $user = $this->userService->getUserById($userId);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Get form data
            $formData = [
                'name' => $request->request->get('name'),
                'email' => $request->request->get('email'),
                'phone_number' => $request->request->get('phone_number'),
                'role' => $request->request->get('role'),
                'gender' => $request->request->get('gender'),
                'account_status' => $request->request->get('account_status'),
                'date_of_birth' => $request->request->get('date_of_birth'),
                'profilePicture' => $request->request->get('profilePicture'),
                'is_verified' => $request->request->getBoolean('is_verified')
            ];
            
            // Update the user
            $user = $this->userService->updateUser($user, $formData);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id_user' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage(),
                'errors' => ['general' => $e->getMessage()]
            ], 500);
        }
    }

    #[Route('/admin/users/api/{id}', name: 'admin_api_user_get', methods: ['GET'])]
    public function getUserApi(int $id): JsonResponse
    {
        try {
            $this->logger->info('User API request for ID: ' . $id);
            
            $user = $this->userService->getUserById($id);
            if (!$user) {
                $this->logger->warning('User not found with ID: ' . $id);
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Handle enum values by converting them to strings
            $role = $user->getRole();
            $roleValue = is_object($role) && method_exists($role, 'value') ? $role->value : $role;
            
            $accountStatus = $user->getAccount_status();
            $accountStatusValue = is_object($accountStatus) && method_exists($accountStatus, 'value') ? $accountStatus->value : $accountStatus;
            
            $gender = $user->getGender();
            $genderValue = is_object($gender) && method_exists($gender, 'value') ? $gender->value : $gender;
            
            $status = $user->getStatus();
            $statusValue = is_object($status) && method_exists($status, 'value') ? $status->value : $status;

            return new JsonResponse([
                'id_user' => $user->getId_user(),
                'id' => $user->getId_user(), // Adding 'id' alias for consistency
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone_number' => $user->getPhone_number(),
                'phoneNumber' => $user->getPhone_number(), // Adding alias for frontend compatibility
                'role' => $roleValue,
                'account_status' => $accountStatusValue,
                'accountStatus' => $accountStatusValue, // Adding alias for frontend compatibility
                'gender' => $genderValue,
                'is_verified' => $user->is_verified(),
                'isVerified' => $user->is_verified(), // Adding alias for frontend compatibility
                'profile_picture' => $user->getProfilePicture() ? $user->getProfilePicture() : '/images/default-avatar.png',
                'profilePicture' => $user->getProfilePicture() ? $user->getProfilePicture() : '/images/default-avatar.png', // Adding alias
                'date_of_birth' => $user->getDate_of_birth() ? $user->getDate_of_birth()->format('Y-m-d') : null,
                'dateOfBirth' => $user->getDate_of_birth() ? $user->getDate_of_birth()->format('Y-m-d') : null, // Adding alias
                'status' => $statusValue
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JsonResponse(['error' => 'Failed to fetch user'], 500);
        }
    }
}