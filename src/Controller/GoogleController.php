<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\ACCOUNT_STATUS;
use App\Enum\GENDER;
use App\Enum\ROLE;
use App\Enum\STATUS;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Psr\Log\LoggerInterface;

class GoogleController extends AbstractController
{
    private $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * @Route("/connect/google", name="connect_google")
     */
    public function connectToGoogle(ClientRegistry $clientRegistry): Response
    {
        try {
            // Log the attempt
            $this->logger->info("Starting Google OAuth connection process");
            
            // Get the Google client
            $client = $clientRegistry->getClient('google');
            
            // Request only basic scopes to avoid OAuth verification requirements
            return $client->redirect([
                'email', 
                'profile',
                'openid'
                // Removed these sensitive scopes that require verification:
                // 'https://www.googleapis.com/auth/user.birthday.read',
                // 'https://www.googleapis.com/auth/user.phonenumbers.read'
            ]);
            
        } catch (\Exception $e) {
            // Log any errors
            $this->logger->error('Google OAuth error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'Failed to connect to Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/connect/google/check", name="connect_google_check")
     */
    public function connectCheckAction(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): Response {
        try {
            $client = $clientRegistry->getClient('google');
            
            // The OAuth2 client handles the redirect parameters for you
            $user = $client->fetchUser();
            
            // Email address is unique in our system
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            
            if ($existingUser) {
                $this->loginUser($existingUser, $tokenStorage);
                $this->addFlash('success', 'Welcome back! You have successfully logged in with Google.');
            } else {
                // Create a new user from Google data
                $newUser = new User();
                $newUser->setEmail($user->getEmail());
                $newUser->setName($user->getName());
                
                // Set default gender (using MALE as default)
                $newUser->setGender(GENDER::MALE);
                
                // Set default role
                $newUser->setRole(ROLE::CLIENT);
                
                // Set default status
                $newUser->setStatus(STATUS::OFFLINE);
                $newUser->setAccountStatus(ACCOUNT_STATUS::ACTIVE);
                  // Generate a truly unique phone number for Google users that follows Tunisian format
                // Start with a valid Tunisian prefix (2, 4, 5, or 9)
                $prefixes = ['2', '4', '5', '9'];
                $uniquePhone = '';
                $isUnique = false;
                
                // Keep trying until we find a unique phone number
                while (!$isUnique) {
                    // Generate a random 8-digit number that starts with a valid Tunisian prefix
                    $prefix = $prefixes[array_rand($prefixes)];
                    
                    // Create 7 more random digits (total 8 digits for Tunisian number)
                    $randomDigits = '';
                    for ($i = 0; $i < 7; $i++) {
                        $randomDigits .= mt_rand(0, 9);
                    }
                    
                    $uniquePhone = $prefix . $randomDigits;
                    
                    // Check if this phone number is already used
                    $existingUser = $em->getRepository(User::class)->findOneBy(['phone_number' => $uniquePhone]);
                    if (!$existingUser) {
                        $isUnique = true;
                    }
                    
                    // Extra safety - add timestamp as part of the phone number if we're having trouble
                    // finding a unique number after multiple attempts
                    if (!$isUnique && mt_rand(1, 10) > 8) { // 20% chance after each failed attempt
                        // Use timestamp to ensure uniqueness
                        $timestamp = substr(time(), -7);
                        $uniquePhone = $prefix . $timestamp;
                        
                        // Final check
                        $existingUser = $em->getRepository(User::class)->findOneBy(['phone_number' => $uniquePhone]);
                        if (!$existingUser) {
                            $isUnique = true;
                        }
                    }
                }
                
                $newUser->setPhone_number($uniquePhone);
                
                // Set a random password
                $randomPassword = bin2hex(random_bytes(8));
                $hashedPassword = $passwordHasher->hashPassword($newUser, $randomPassword);
                $newUser->setPassword($hashedPassword);
                
                // Mark as verified since they've verified their email with Google
                $newUser->setIsVerified(true);
                
                $em->persist($newUser);
                $em->flush();
                
                $this->loginUser($newUser, $tokenStorage);
                $this->addFlash('success', 'Your account has been created successfully with Google.');
            }
            
            return $this->redirectToRoute('app_front_home');
            
        } catch (IdentityProviderException $e) {
            $this->logger->error('Google OAuth identity error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'Failed to authenticate with Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during Google authentication: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'An unexpected error occurred: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
    
    /**
     * Helper method to log in a user
     */
    private function loginUser(User $user, TokenStorageInterface $tokenStorage): void
    {
        // Create the token
        $token = new UsernamePasswordToken(
            $user,
            'main', // Firewall name
            $user->getRoles()
        );
        
        // Set the token
        $tokenStorage->setToken($token);
        
        $this->logger->info('User logged in with Google: ' . $user->getEmail());
    }
}
