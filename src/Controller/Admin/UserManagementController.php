<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use App\Enum\ROLE;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\GENDER;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/users-management')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'admin_users_management', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/users/management.html.twig', [
            'account_statuses' => ACCOUNT_STATUS::cases(),
            'roles' => ROLE::cases(),
            'genders' => GENDER::cases(),
        ]);
    }

    #[Route('/api/list', name: 'admin_api_users_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        $data = [];
        foreach ($users as $user) {
            $profilePicture = $user->getProfilePicture() ?: 'default-avatar.png';
            
            $data[] = [
                'id' => $user->getId_user(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone_number(),
                'role' => $user->getRole(),
                'accountStatus' => $user->getAccount_status()->value,
                'isVerified' => $user->isVerified(),
                'profilePicture' => $profilePicture,
                'gender' => $user->getGender()->value,
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/api/{id}', name: 'admin_api_user_get', methods: ['GET'])]
    public function apiGetUser(User $user): JsonResponse
    {
        $userData = [
            'id' => $user->getId_user(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone_number(),
            'role' => $user->getRole(),
            'accountStatus' => $user->getAccount_status()->value,
            'isVerified' => $user->isVerified(),
            'profilePicture' => $user->getProfilePicture() ?: 'default-avatar.png',
            'gender' => $user->getGender()->value,
            'dateOfBirth' => $user->getDateOfBirth(),
        ];
        
        return $this->json($userData);
    }

    #[Route('/api/{id}/update', name: 'admin_api_user_update', methods: ['POST'])]
    public function apiUpdateUser(Request $request, User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Invalid data format'], 400);
        }
        
        try {
            if (isset($data['name'])) {
                $user->setName($data['name']);
            }
            
            if (isset($data['email'])) {
                $user->setEmail($data['email']);
            }
            
            if (isset($data['phone'])) {
                $user->setPhone_number($data['phone']);
            }
            
            if (isset($data['role'])) {
                $user->setRole(ROLE::from($data['role']));
            }
            
            if (isset($data['accountStatus'])) {
                $user->setAccount_status(ACCOUNT_STATUS::from($data['accountStatus']));
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            }
            
            if (isset($data['isVerified'])) {
                $user->setIs_verified((bool) $data['isVerified']);
            }
            
            if (isset($data['gender'])) {
                $user->setGender(GENDER::from($data['gender']));
            }
            
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['success' => false, 'errors' => $errorMessages], 400);
            }
            
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'accountStatus' => $user->getAccount_status()->value,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/create', name: 'admin_api_user_create', methods: ['POST'])]
    public function apiCreateUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Invalid data format'], 400);
        }
        
        try {
            $user = new User();
            
            // Required fields
            if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
                return $this->json(['success' => false, 'message' => 'Missing required fields'], 400);
            }
            
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            
            // Optional fields with defaults
            $user->setRole(isset($data['role']) ? ROLE::from($data['role']) : ROLE::CLIENT);
            $user->setAccount_status(isset($data['accountStatus']) ? ACCOUNT_STATUS::from($data['accountStatus']) : ACCOUNT_STATUS::ACTIVE);
            $user->setIs_verified(isset($data['isVerified']) ? (bool) $data['isVerified'] : false);
            $user->setGender(isset($data['gender']) ? GENDER::from($data['gender']) : GENDER::OTHER);
            
            if (isset($data['phone'])) {
                $user->setPhone_number($data['phone']);
            }
            
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['success' => false, 'errors' => $errorMessages], 400);
            }
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'accountStatus' => $user->getAccount_status()->value,
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/{id}/delete', name: 'admin_api_user_delete', methods: ['POST'])]
    public function apiDeleteUser(User $user): JsonResponse
    {
        try {
            $userId = $user->getId_user();
            
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'User deleted successfully',
                'id' => $userId
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()], 500);
        }
    }
}
