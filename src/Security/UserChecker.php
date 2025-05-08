<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Check if the user is banned
        if ($user->getAccountStatus() === 'BANNED') {
            throw new CustomUserMessageAuthenticationException(
                'Your account has been banned. Please contact support for assistance.'
            );
        }

        // Check if the user is suspended
        if ($user->getAccountStatus() === 'SUSPENDED') {
            throw new CustomUserMessageAuthenticationException(
                'Your account has been temporarily suspended. Please try again later or contact support.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No post-authentication checks needed
    }
} 