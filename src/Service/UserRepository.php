<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserRepository
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function isUserDriver($user): bool
    {
        // Basic mock implementation
        return false;
    }

    public function isUserAdmin($user): bool
    {
        // Basic mock implementation
        return false;
    }
} 