<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    private UserService $userService;
    private SerializerInterface $serializer;

    public function __construct(UserService $userService, SerializerInterface $serializer)
    {
        $this->userService = $userService;
        $this->serializer = $serializer;
    }

    #[Route('/users', name: 'api_users', methods: ['GET'])]
    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers();

            
            $serializedUsers = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);
            error_log('Serialized Users: ' . $serializedUsers);

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
}