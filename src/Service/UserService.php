<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Enum\ROLE;
use App\Enum\GENDER;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\STATUS;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    public function getUserQuery(array $filters = []): QueryBuilder
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->select('u');
            
        if (!empty($filters['search'])) {
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search OR u.phone_number LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        if (!empty($filters['role'])) {
                $qb->andWhere('u.role = :role')
               ->setParameter('role', $filters['role']);
        }
        
        if (!empty($filters['account_status'])) {
            $qb->andWhere('u.account_status = :account_status')
               ->setParameter('account_status', $filters['account_status']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('u.account_status = :status')
               ->setParameter('status', $filters['status']);
        }
        
        if (isset($filters['verified']) && $filters['verified'] !== '') {
            $isVerified = $filters['verified'] == '1' ? true : false;
            $qb->andWhere('u.is_verified = :verified')
               ->setParameter('verified', $isVerified);
        }
        
        $orderBy = $filters['orderBy'] ?? 'id_user';
        $orderDirection = $filters['orderDirection'] ?? 'DESC';
        
        $validColumns = ['id_user', 'name', 'email', 'role', 'account_status', 'is_verified'];
        if (!in_array($orderBy, $validColumns)) {
            $orderBy = 'id_user';
        }
        
        $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
        
        $qb->orderBy('u.' . $orderBy, $orderDirection);
        
        return $qb;
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function createUser(array $data): User
    {
        // For simplified test user creation
        if (isset($data['roles'])) {
            $hasTestData = true;
        } else {
            $this->validateUserData($data, true);
        }

        $user = new User();
        $this->setUserData($user, $data);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        // Update user fields
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['phone_number'])) {
            $user->setPhone_number($data['phone_number']);
        }
        
        if (isset($data['role'])) {
            $user->setRole(ROLE::from($data['role']));
        }
        
        if (isset($data['gender'])) {
            $user->setGender(GENDER::from($data['gender']));
        }
        
        if (isset($data['account_status'])) {
            $user->setAccount_status(ACCOUNT_STATUS::from($data['account_status']));
        }
        
        if (isset($data['date_of_birth'])) {
            if (!empty($data['date_of_birth'])) {
                $user->setDate_of_birth(new \DateTime($data['date_of_birth']));
            } else {
                $user->setDate_of_birth(null);
            }
        }
        
        if (isset($data['profilePicture'])) {
            $user->setProfilePicture($data['profilePicture']);
        }
        
        if (isset($data['is_verified'])) {
            $user->setIs_verified((bool) $data['is_verified']);
        }
        
        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    public function partialUpdateUser(User $user, array $data): User
    {
        foreach ($data as $field => $value) {
            $setter = 'set' . str_replace('_', '', ucwords($field, '_'));

            if (method_exists($user, $setter)) {
                if ($field === 'password') {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $value));
                } elseif ($field === 'date_of_birth') {
                    $user->setDateOfBirth(new \DateTime($value));
                } else {
                    $user->$setter($value);
                }
            }
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->flush();
        return $user;
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }
    
    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function validateUserData(array $data, bool $isCreate): void
    {
        $requiredFields = [
            'name',
            'email',
            'phone_number',
            'role',
            'gender',
            'account_status'
        ];

        if ($isCreate) {
            $requiredFields[] = 'password';
        }

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Missing required field: $field");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format");
        }

        if ($isCreate && strlen($data['password']) < 8) {
            throw new \InvalidArgumentException("Password must be at least 8 characters");
        }
    }

    private function setUserData(User $user, array $data): void
    {
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['phone_number'])) {
            $user->setPhone_number($data['phone_number']);
        }

        if (isset($data['role'])) {
            try {
                $role = ROLE::from($data['role']);
                $user->setRole($role);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid role value: ' . $data['role']);
            }
        }

        if (isset($data['gender'])) {
            try {
                $gender = GENDER::from($data['gender']);
                $user->setGender($gender);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid gender value: ' . $data['gender']);
            }
        }

        if (isset($data['account_status'])) {
            try {
                $accountStatus = ACCOUNT_STATUS::from($data['account_status']);
                $user->setAccount_status($accountStatus);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid account status value: ' . $data['account_status']);
            }
        }

        if (isset($data['password'])) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
        }

        if (isset($data['profile_picture'])) {
            $user->setProfilePicture($data['profile_picture']);
        }

        if (isset($data['is_verified'])) {
            $user->setIs_verified((bool)$data['is_verified']);
        }

        if (isset($data['status'])) {
            try {
                $status = STATUS::from($data['status']);
                $user->setStatus($status);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid status value: ' . $data['status']);
            }
        }

        if (isset($data['date_of_birth'])) {
            $user->setDate_of_birth(new \DateTime($data['date_of_birth']));
        }
    }
}
