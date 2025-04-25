<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Form\AdminUserDeleteType;
use App\Repository\UserRepository;
use App\Enum\ROLE;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\GENDER;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/admin/users-management')]
class UserManagementController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private $passwordHasher;
    private $logger;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }
    
    #[Route('/', name: 'admin_users_management')]
    public function index(): Response
    {
        // Create forms for the templates
        $newForm = $this->createForm(AdminUserType::class, new User());
        $editForm = $this->createForm(AdminUserType::class, new User(), ['is_edit' => true]);
        $deleteForm = $this->createForm(AdminUserDeleteType::class);
        
        return $this->render('admin/users/users.html.twig', [
            'roles' => ROLE::cases(),
            'account_statuses' => ACCOUNT_STATUS::cases(),
            'genders' => GENDER::cases(),
            'form_new' => $newForm,
            'form_edit' => $editForm,
            'form_delete' => $deleteForm
        ]);
    }
    
    #[Route('/api/list', name: 'admin_api_users_list', methods: ['GET'])]
    public function apiList(Request $request): JsonResponse
    {
        // Get query parameters for filtering and sorting
        $search = $request->query->get('search', '');
        $roleFilter = $request->query->get('role', '');
        $statusFilter = $request->query->get('status', '');
        $verificationFilter = $request->query->get('verified', '');
        $sortBy = $request->query->get('sortBy', 'name');
        $sortDir = $request->query->get('sortDir', 'asc');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $offset = ($page - 1) * $limit;
        
        // Save filters to session for persistence
        $session = $request->getSession();
        $session->set('user_management_filters', [
            'search' => $search,
            'role' => $roleFilter,
            'status' => $statusFilter,
            'verified' => $verificationFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'page' => $page,
            'limit' => $limit
        ]);
        
        // Create criteria based on filters
        $criteria = [];
        
        if ($roleFilter) {
            $criteria['role'] = $roleFilter;
        }
        
        if ($statusFilter) {
            $criteria['account_status'] = $statusFilter;
        }
        
        if ($verificationFilter !== '') {
            $criteria['is_verified'] = $verificationFilter === 'true' || $verificationFilter === '1';
        }
        
        // Use the repository to find users with criteria and sorting
        if (!empty($search)) {
            $users = $this->userRepository->findBySearchTerm($search, $criteria, $sortBy, $sortDir, $limit, $offset);
            $total = $this->userRepository->countBySearchTerm($search, $criteria);
        } else {
            $users = $this->userRepository->findBy(
                $criteria, 
                [$sortBy => $sortDir], 
                $limit, 
                $offset
            );
            $total = $this->userRepository->count($criteria);
        }
        
        $data = [];
        
        foreach ($users as $user) {
            $profilePicture = $user->getProfilePicture() ?: 'default-avatar.png';
            
            // Make sure profile picture is just the filename, not an external URL
            if (strpos($profilePicture, 'http') === 0) {
                // Extract just the filename or use a default
                $parts = explode('/', $profilePicture);
                $profilePicture = end($parts);
            }
            
            $data[] = [
                'id' => $user->getId_user(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phoneNumber' => $user->getPhone_number(),
                'role' => $user->getRole(),
                'accountStatus' => $user->getAccount_status(),
                'gender' => $user->getGender(),
                'isVerified' => $user->is_verified(),
                'profilePicture' => $profilePicture,
                'dateOfBirth' => $user->getDate_of_birth() ? $user->getDate_of_birth()->format('Y-m-d') : null
            ];
        }
        
        // Return response as proper JSON with pagination information
        return new JsonResponse([
            'total' => $total,
            'rows' => $data
        ]);
    }
    
    #[Route('/api/{id}', name: 'admin_api_user_get', methods: ['GET'])]
    public function apiGetUser(User $user): JsonResponse
    {
        $profilePicture = $user->getProfilePicture() ?: 'default-avatar.png';
        
        // Ensure we're only using the filename, not a complete URL
        if (strpos($profilePicture, 'http') === 0) {
            $parts = explode('/', $profilePicture);
            $profilePicture = end($parts);
        }
        
        $data = [
            'id' => $user->getId_user(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phoneNumber' => $user->getPhone_number(),
            'role' => $user->getRole(),
            'accountStatus' => $user->getAccount_status(),
            'gender' => $user->getGender(),
            'isVerified' => $user->is_verified(),
            'profilePicture' => $profilePicture,
            'dateOfBirth' => $user->getDate_of_birth() ? $user->getDate_of_birth()->format('Y-m-d') : null
        ];
        
        return new JsonResponse($data);
    }
    
    #[Route('/api/{id}/update', name: 'admin_api_user_update', methods: ['POST'])]
    public function apiUpdateUser(Request $request, User $user): JsonResponse
    {
        try {
            $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
            
            // Decode JSON request data
            $jsonData = json_decode($request->getContent(), true);
            if (!is_array($jsonData)) {
                throw new \InvalidArgumentException('Invalid JSON data received');
            }
            
            $request->request->replace($jsonData);
            $form->submit($request->request->all());
            
            if ($form->isValid()) {
                // Handle password update if included
                if (!empty($jsonData['plainPassword'])) {
                    $user->setPassword(
                        $this->passwordHasher->hashPassword($user, $jsonData['plainPassword'])
                    );
                }
                
                $this->entityManager->flush();
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => [
                        'id' => $user->getId_user(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'phoneNumber' => $user->getPhone_number(),
                        'role' => $user->getRole(),
                        'accountStatus' => $user->getAccount_status()
                    ]
                ]);
            }
            
            return new JsonResponse([
                'success' => false,
                'errors' => $this->getFormErrors($form)
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/api/create', name: 'admin_api_user_create', methods: ['POST'])]
    public function apiCreateUser(Request $request): JsonResponse
    {
        try {
            $user = new User();
            $form = $this->createForm(AdminUserType::class, $user);
            
            // Decode JSON request data
            $jsonData = json_decode($request->getContent(), true);
            if (!is_array($jsonData)) {
                throw new \InvalidArgumentException('Invalid JSON data received');
            }
            
            $request->request->replace($jsonData);
            $form->submit($request->request->all());
            
            if ($form->isValid()) {
                // Set password if provided
                if (!empty($jsonData['plainPassword'])) {
                    $user->setPassword(
                        $this->passwordHasher->hashPassword($user, $jsonData['plainPassword'])
                    );
                } else {
                    throw new \InvalidArgumentException('Password is required for new users');
                }
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'User created successfully',
                    'userId' => $user->getId_user()
                ], 201);
            }
            
            return new JsonResponse([
                'success' => false,
                'errors' => $this->getFormErrors($form)
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/api/{id}/delete', name: 'admin_api_user_delete', methods: ['POST'])]
    public function apiDeleteUser(User $user): JsonResponse
    {
        try {
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
            ], 500);
        }
    }
    
    /**
     * Quick update of user account status (for banning/unbanning users)
     */
    #[Route('/api/{id}/status', name: 'admin_api_user_status_update', methods: ['POST'])]
    public function apiUpdateUserStatus(Request $request, User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['accountStatus']) || !in_array($data['accountStatus'], ['ACTIVE', 'SUSPENDED', 'BANNED'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid account status provided'
                ], 400);
            }
            
            $previousStatus = $user->getAccount_status();
            $newStatus = $data['accountStatus'];
            
            // Use enum for type safety
            $user->setAccount_status(ACCOUNT_STATUS::from($newStatus));
            $this->entityManager->flush();
            
            // Log the status change
            $this->logger->info('User status changed', [
                'user_id' => $user->getId_user(),
                'user_email' => $user->getEmail(),
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => $this->getUser()->getEmail()
            ]);
            
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('User "%s" status changed from %s to %s', $user->getName(), $previousStatus, $newStatus),
                'data' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'accountStatus' => $newStatus
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating user status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper function to get form errors
     */
    private function getFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $fieldName = $error->getOrigin()->getName();
            $message = $error->getMessage();
            $errors[] = "$fieldName: $message";
        }
        return $errors;
    }

    /**
     * Renders the new user form for AJAX requests
     */
    #[Route('/form/new', name: 'admin_user_new', options: ['expose' => true])]
    public function newUserForm(Request $request): Response
    {
        $form = $this->createForm(AdminUserType::class, new User());
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/users/new.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        
        return $this->redirectToRoute('admin_users_management');
    }

    /**
     * Renders the edit user form for AJAX requests
     */
    #[Route('/form/{id}/edit', name: 'admin_user_edit', options: ['expose' => true])]
    public function editUserForm(Request $request, User $user): Response
    {
        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/users/edit.html.twig', [
                'form' => $form->createView(),
                'user' => $user
            ]);
        }
        
        return $this->redirectToRoute('admin_users_management');
    }

    /**
     * Renders the delete user form for AJAX requests
     */
    #[Route('/form/{id}/delete', name: 'admin_user_delete', options: ['expose' => true])]
    public function deleteUserForm(Request $request, User $user): Response
    {
        $form = $this->createForm(AdminUserDeleteType::class);
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/users/delete.html.twig', [
                'form' => $form->createView(),
                'user' => $user
            ]);
        }
        
        return $this->redirectToRoute('admin_users_management');
    }
}
