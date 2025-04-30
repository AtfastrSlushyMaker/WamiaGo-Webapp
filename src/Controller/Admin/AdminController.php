<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Form\AdminUserDeleteType;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Location;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\ROLE;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'rideShares' => 150,
            'taxiBookings' => 53,
            'bicycleRentals' => 44,
            'relocationBookings' => 65,
        ];

        return $this->render('back-office/dashboard.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function users(UserService $userService): Response
    {
        $users = $userService->getAllUsers();
        $locations = $this->entityManager->getRepository(Location::class)->findAll();
        
        // Create the forms needed by the included templates
        $newUser = new User();
        $form_new = $this->createForm(AdminUserType::class, $newUser, [
            'is_edit' => false
        ]);
        
        $editUser = new User();
        $form_edit = $this->createForm(AdminUserType::class, $editUser, [
            'is_edit' => true
        ]);
        
        $deleteUser = new User();
        $form_delete = $this->createForm(AdminUserDeleteType::class, $deleteUser);
        
        return $this->render('admin/users/users.html.twig', [
            'users' => $users,
            'locations' => $locations,
            'account_statuses' => ACCOUNT_STATUS::cases(),
            'roles' => ROLE::cases(),
            'form_new' => $form_new->createView(),
            'form_edit' => $form_edit->createView(),
            'form_delete' => $form_delete->createView()
        ]);
    }
    
    #[Route('/admin/users/new', name: 'admin_user_new', methods: ['POST'])]
    public function newUser(Request $request): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user, [
            'is_edit' => false
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);
            
            // Save the user
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ]);
        }
        
        // Get validation errors
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Form validation failed',
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }
    
    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
    public function editUser(Request $request, int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $form = $this->createForm(AdminUserType::class, $user, [
            'is_edit' => true
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Check if we need to update the password
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                );
                $user->setPassword($hashedPassword);
            }
            
            // Save the user
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ]);
        }
        
        // Get validation errors
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Form validation failed',
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }
    
    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $form = $this->createForm(AdminUserDeleteType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Delete the user
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Invalid form submission'
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/admin/ride-sharing', name: 'admin_ride_sharing')]
    public function rideSharing(): Response
    {
        return $this->render('back-office/ride-sharing.html.twig');
    }

    #[Route('/admin/taxi-bookings', name: 'admin_taxi_bookings')]
    public function taxiBookings(): Response
    {
        return $this->render('back-office/taxi-bookings.html.twig');
    }

    #[Route('/admin/bicycle-rentals', name: 'admin_bicycle_rentals')]
    public function bicycleRentals(): Response
    {
        return $this->render('back-office/bicycle-rentals.html.twig');
    }

    #[Route('/admin/relocations', name: 'admin_relocations')]
    public function relocations(): Response
    {
        return $this->render('back-office/relocations.html.twig');
    }

    #[Route('/admin/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        return $this->render('back-office/settings.html.twig');
    }

    #[Route('/admin/profile', name: 'admin_profile')]
    public function profile(): Response
    {
        return $this->render('back-office/profile.html.twig');
    }

    #[Route('/admin/test', name: 'admin_test')]
    public function test(): Response
    {
        return $this->render('admin/test.html.twig');
    }

    #[Route('/admin/api/users', name: 'admin_api_users', methods: ['GET'])]
    public function apiUsers(UserService $userService, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Stop any output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Simple implementation with error handling
        try {
            // Get pagination parameters
            $page = $request->query->getInt('page', 1);
            $itemsPerPage = $request->query->getInt('items', 10);
            $itemsPerPage = min(max($itemsPerPage, 5), 100); // Constrain between 5-100
            
            // Get filter parameters
            $filters = [
                'search' => $request->query->get('search'),
                'role' => $request->query->get('role'),
                'status' => $request->query->get('status'),
                'verified' => $request->query->get('verified'),
                'orderBy' => $request->query->get('orderBy', 'id_user'),
                'orderDirection' => $request->query->get('orderDirection', 'DESC')
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            // Special handling for status filter
            if (isset($filters['status'])) {
                // Convert to string directly instead of using enum - we'll do string comparison in SQL
                $filters['status'] = (string)$filters['status'];
            }
            
            // Get paginated query
            $query = $userService->getUserQuery($filters);
            
            // Calculate offset
            $offset = ($page - 1) * $itemsPerPage;
            
            // Execute query with pagination
            $query->setFirstResult($offset)
                  ->setMaxResults($itemsPerPage);
            
            // Get users for current page
            $usersForPage = $query->getQuery()->getResult();
            
            // Get total count for pagination
            $countQuery = clone $query;
            $countQuery->select('COUNT(u.id_user)')
                       ->setFirstResult(null)
                       ->setMaxResults(null);
            $totalUsers = $countQuery->getQuery()->getSingleScalarResult();
            
            $formattedUsers = [];
            foreach ($usersForPage as $user) {
                // Handle potential null dates
                $dateOfBirth = null;
                if ($user->getDate_of_birth()) {
                    try {
                        $dateOfBirth = $user->getDate_of_birth()->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If date formatting fails, leave as null
                    }
                }
                
                // Get role as string, handling both enum and string cases
                $role = $user->getRole();
                if (is_object($role) && method_exists($role, 'value')) {
                    $role = $role->value;
                }
                
                // Get account status as string, handling both enum and string cases
                $accountStatus = $user->getAccount_status();
                if (is_object($accountStatus) && method_exists($accountStatus, 'value')) {
                    $accountStatus = $accountStatus->value;
                }
                
                // Get gender as string, handling both enum and string cases
                $gender = $user->getGender();
                if (is_object($gender) && method_exists($gender, 'value')) {
                    $gender = $gender->value;
                }
                
                $formattedUsers[] = [
                    'id_user' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'phone_number' => $user->getPhone_number(),
                    'role' => $role ?: 'CLIENT',
                    'account_status' => $accountStatus ?: 'ACTIVE',
                    'gender' => $gender,
                    'is_verified' => $user->is_verified(),
                    'profile_picture' => $user->getProfilePicture() ? $user->getProfilePicture() : '/images/default-avatar.png',
                    'date_of_birth' => $dateOfBirth
                ];
            }
            
            // Calculate user stats - use string values directly instead of enum constants
            $statsQuery = $entityManager->createQueryBuilder()
                ->select('CASE 
                    WHEN u.account_status = \'ACTIVE\' THEN \'ACTIVE\' 
                    WHEN u.account_status = \'SUSPENDED\' THEN \'SUSPENDED\' 
                    WHEN u.account_status = \'BANNED\' THEN \'BANNED\' 
                    ELSE \'OTHER\' 
                  END as status_string', 'COUNT(u.id_user) as count')
                ->from(User::class, 'u')
                ->groupBy('status_string')
                ->getQuery();
            
            $statsResults = $statsQuery->getResult();
            
            $stats = [
                'total' => $totalUsers,
                'active' => 0,
                'suspended' => 0,
                'banned' => 0
            ];
            
            // Process stats - now status_string is guaranteed to be a string
            foreach ($statsResults as $stat) {
                $status = $stat['status_string'];
                
                if ($status === 'ACTIVE') {
                    $stats['active'] = $stat['count'];
                } else if ($status === 'SUSPENDED') {
                    $stats['suspended'] = $stat['count'];
                } else if ($status === 'BANNED') {
                    $stats['banned'] = $stat['count'];
                }
            }
            
            // Create a direct response bypassing Symfony's response handling
            $jsonData = json_encode([
                'users' => $formattedUsers,
                'total' => $totalUsers,
                'page' => $page,
                'pages' => ceil($totalUsers / $itemsPerPage),
                'itemsPerPage' => $itemsPerPage,
                'stats' => $stats
            ]);
            
            // Create a raw response
            $response = new Response(
                $jsonData,
                200,
                [
                    'Content-Type' => 'application/json',
                    'X-Debug-Info' => 'Direct Response'
                ]
            );
            $response->send();
            exit; // Terminate execution to prevent any further output
        } catch (\Exception $e) {
            // Create a direct error response
            $jsonError = json_encode([
                'error' => 'Failed to fetch users: ' . $e->getMessage()
            ]);
            
            $response = new Response(
                $jsonError,
                500,
                ['Content-Type' => 'application/json']
            );
            $response->send();
            exit; // Terminate execution
        }
    }

    #[Route('/admin/api/test-json', name: 'admin_api_test_json', methods: ['GET'])]
    public function testJson(): Response
    {
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Direct JSON output
        $jsonData = json_encode([
            'test' => 'success',
            'timestamp' => time()
        ]);
        
        // Create and immediately send response
        $response = new Response(
            $jsonData,
            200,
            ['Content-Type' => 'application/json']
        );
        $response->send();
        exit; // Terminate execution
    }
}