<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users', name: 'api_users_')]
class UserApiController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getIdUser(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone_number' => $user->getPhoneNumber(),
                'gender' => $user->getGender(),
                'dateOfBirth' => $user->getDateOfBirth() ? $user->getDateOfBirth()->format('Y-m-d') : null,
                'role' => $user->getRole(),
                'accountStatus' => $user->getStatus(),
                'isVerified' => $user->isVerified(),
                'profilePicture' => $user->getProfilePicture()
            ];
        }
        
        return new JsonResponse([
            'status' => 'success',
            'data' => $usersData
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        
        $userData = [
            'id' => $user->getIdUser(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phone_number' => $user->getPhoneNumber(),
            'gender' => $user->getGender(),
            'dateOfBirth' => $user->getDateOfBirth() ? $user->getDateOfBirth()->format('Y-m-d') : null,
            'role' => $user->getRole(),
            'accountStatus' => $user->getStatus(),
            'isVerified' => $user->isVerified(),
            'profilePicture' => $user->getProfilePicture()
        ];
        
        return new JsonResponse([
            'status' => 'success',
            'data' => $userData
        ]);
    }
}
