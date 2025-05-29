<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleOAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
         
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();


                $existingUser = $this->userRepository->findOneBy(['email' => $email]);

                if ($existingUser) {
                    return $existingUser;
                }

                $user = new User();
                $user->setEmail($email);
              
                $user->setName($googleUser->getFirstName() . ' ' . $googleUser->getLastName());
              
                $user->setGender(\App\Enum\GENDER::MALE);
                
                $user->setRole(\App\Enum\ROLE::CLIENT);
                
                // Generate a unique phone number based on their email to prevent duplicate constraint errors
                // This creates a temporary phone number that will be different for each user
                $emailHash = substr(md5($email), 0, 5); // Get first 5 chars of email hash
                $uniquePhoneNumber = '2' . str_pad(abs(crc32($emailHash)) % 9999999, 7, '0', STR_PAD_LEFT);
                $user->setPhoneNumber($uniquePhoneNumber);
                
                // Try to get user data from Google
                $userData = $googleUser->toArray();
                
            
                
                if ($googleUser->getAvatar()) {
                    $user->setProfilePicture($googleUser->getAvatar());
                }
                
                
                if (method_exists($googleUser, 'getPhoneNumber') && $googleUser->getPhoneNumber()) {
                    $user->setPhoneNumber($googleUser->getPhoneNumber());
                    error_log('Using phone number from getPhoneNumber method: ' . $googleUser->getPhoneNumber());
                } elseif (!empty($userData['phone_number'])) {
                    $user->setPhoneNumber($userData['phone_number']);
                    error_log('Using phone number from phone_number field: ' . $userData['phone_number']);
                } elseif (!empty($userData['phoneNumber'])) {
                    $user->setPhoneNumber($userData['phoneNumber']);
                    error_log('Using phone number from phoneNumber field: ' . $userData['phoneNumber']);
                } elseif (!empty($userData['phone'])) {
                    $user->setPhoneNumber($userData['phone']);
                    error_log('Using phone number from phone field: ' . $userData['phone']);
                } else {
                    $user->setPhoneNumber('20000000');
                    error_log('Unable to find phone number in Google data, using default');
                }
               
                if (method_exists($googleUser, 'getBirthday') && $googleUser->getBirthday()) {
                    try {
                        $birthDateObj = new \DateTime($googleUser->getBirthday());
                        $user->setDate_of_birth($birthDateObj);
                        error_log('Successfully set birth date from getBirthday method: ' . $googleUser->getBirthday());
                    } catch (\Exception $e) {
                        error_log('Failed to parse birth date from getBirthday: ' . $e->getMessage());
                    }
                } elseif (!empty($userData['birthdate']) || !empty($userData['birthday']) || !empty($userData['birth_date'])) {
                    $birthdate = !empty($userData['birthdate']) ? $userData['birthdate'] : 
                              (!empty($userData['birthday']) ? $userData['birthday'] : $userData['birth_date']);
                    try {
                        $birthDateObj = new \DateTime($birthdate);
                        $user->setDate_of_birth($birthDateObj);
                        error_log('Successfully set birth date from array field: ' . $birthdate);
                    } catch (\Exception $e) {
                        error_log('Failed to parse birth date from array field: ' . $e->getMessage());
                    }
                } else {
                    error_log('No birth date found in Google data');
                }
             
                if ($googleUser->getAvatar()) {
                    $user->setProfilePicture($googleUser->getAvatar());
                }
               
                $randomPassword = bin2hex(random_bytes(8));
                $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
                $user->setPassword($hashedPassword);
                
                $user->setIsVerified(true); 
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
    
        return new RedirectResponse($this->router->generate('app_front_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new RedirectResponse(
            $this->router->generate('app_login', ['error' => $message])
        );
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
