<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class GoogleController extends AbstractController
{
    private $logger;
    
    public function __construct(\Psr\Log\LoggerInterface $logger)
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
            
            // Request ALL scopes including sensitive ones (will only work for test users)
            return $client->redirect([
                'email', 
                'profile',
                'openid',
                'https://www.googleapis.com/auth/user.birthday.read',
                'https://www.googleapis.com/auth/user.phonenumbers.read'
            ], []);
            
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
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): Response {
        $client = $clientRegistry->getClient('google');
        
        try {
            // The OAuth2 client handles the redirect parameters for you
            $user = $client->fetchUser();
            
            // Email address is unique in our system
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            
            if ($existingUser) {
                $this->loginUser($existingUser, $tokenStorage);
                $this->addFlash('success', 'Welcome back! You have successfully logged in with Google.');
            } else {
                // Create a new user from Google data
                $newUser = new User();
                $newUser->setEmail($user->getEmail());
                $newUser->setName($user->getFirstName() . ' ' . $user->getLastName());
                // Set default gender (using MALE as default since OTHER is not available)
                $newUser->setGender(\App\Enum\GENDER::MALE);
                // Set a temporary phone number since database doesn't allow null
                $newUser->setPhoneNumber('20000000');
                // Set default role
                $newUser->setRole(\App\Enum\ROLE::CLIENT);
                
                // Set profile picture from Google if available
                if ($user->getAvatar()) {
                    $newUser->setProfilePicture($user->getAvatar());
                }
                
                // Generate a random secure password (user won't need this)
                $randomPass = bin2hex(random_bytes(10));
                $hashedPassword = $passwordHasher->hashPassword($newUser, $randomPass);
                $newUser->setPassword($hashedPassword);
                
                $newUser->setIsVerified(true); // Skip email verification since Google already verified them
                
                // Persist the new user
                $em->persist($newUser);
                $em->flush();
                
                // Log in the new user
                $this->loginUser($newUser, $tokenStorage);
                $this->addFlash('success', 'Your account has been created! You are now logged in.');
            }
            
            return $this->redirectToRoute('app_front_home');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to authenticate with Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
    
    private function loginUser(User $user, TokenStorageInterface $tokenStorage): void
    {
        // Manual login - create authentication token
        $token = new UsernamePasswordToken(
            $user,
            'main', // Firewall name
            $user->getRoles()
        );
        
        // Set token in the token storage
        $tokenStorage->setToken($token);
        
        // Update the session
        $this->container->get('session')->set('_security_main', serialize($token));
    }
}
