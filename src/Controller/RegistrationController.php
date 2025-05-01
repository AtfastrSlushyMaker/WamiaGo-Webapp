<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Enum\ROLE;
use App\Enum\STATUS;
use App\Enum\ACCOUNT_STATUS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Special handling for CSRF errors in AJAX context
     */
    private function isCsrfError(\Exception $e): bool
    {
        return (
            strpos($e->getMessage(), 'CSRF') !== false || 
            strpos($e->getMessage(), 'csrf') !== false
        );
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserRepository $userRepository): Response
    {
        try {
            if ($this->getUser()) {
                return $this->redirectToRoute('app_front_home');
            }

            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            
            // Try to handle the request, but catch any exceptions
            try {
                $form->handleRequest($request);
                
                // Add logging to debug the signup process
                $this->logger->info('Handling registration form submission.');

                if ($form->isSubmitted()) {
                    $this->logger->info('Form submitted.');

                    if ($form->isValid()) {
                        $this->logger->info('Form is valid. Proceeding with user creation.');
                    } else {
                        $this->logger->error('Form is invalid. Errors:');
                        foreach ($form->getErrors(true) as $error) {
                            $this->logger->error($error->getMessage());
                        }
                    }
                } else {
                    $this->logger->info('Form not submitted yet.');
                }

                if ($form->isSubmitted() && $form->isValid()) {
                    try {
                        // Check if email already exists
                        if ($userRepository->findOneBy(['email' => $user->getEmail()])) {
                            $this->addFlash('error', 'Email is already registered.');
                            
                            if ($request->isXmlHttpRequest()) {
                                return new JsonResponse([
                                    'success' => false,
                                    'message' => 'Email is already registered.'
                                ], Response::HTTP_BAD_REQUEST);
                            }
                            
                            return $this->redirectToRoute('app_register');
                        }

                        // Combine first name and last name
                        $firstName = $form->get('firstName')->getData();
                        $lastName = $form->get('lastName')->getData();
                        $user->setName($firstName . ' ' . $lastName);

                        // Handle gender field
                        $genderValue = $form->get('gender')->getData();
                        try {
                            $gender = \App\Enum\GENDER::from($genderValue);
                            $user->setGender($gender);
                        } catch (\ValueError $e) {
                            $this->logger->error('Invalid gender value: {value}', ['value' => $genderValue]);
                            throw new \InvalidArgumentException('Invalid gender value');
                        }

                        // Hash the password
                        $user->setPassword(
                            $this->passwordHasher->hashPassword(
                                $user,
                                $form->get('plainPassword')->getData()
                            )
                        );

                     
                        $user->setRole(\App\Enum\ROLE::CLIENT);
                        $user->setAccountStatus(\App\Enum\ACCOUNT_STATUS::ACTIVE);
                    
                    
                        try {
                     
                            $this->logger->info('Current status value before assignment: {status}', ['status' => $user->getStatus()]);

                        
                            if (!($user->getStatus() instanceof \App\Enum\STATUS)) {
                                $this->logger->warning('Invalid status detected. Resetting to STATUS::OFFLINE.');
                                $user->setStatus(\App\Enum\STATUS::OFFLINE);
                            }
                        } catch (\TypeError $e) {
                            $this->logger->error('Invalid status value during registration: {message}', ['message' => $e->getMessage()]);
                            throw new \InvalidArgumentException('Invalid status value during registration.');
                        }
                        $user->setIsVerified(false);

                        // Persist the user
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();

                        $this->addFlash('success', 'Registration successful! Please log in.');
                        return $this->redirectToRoute('app_login');
                    } catch (\Exception $e) {
                        $this->logger->error('Error during registration: {message}', ['message' => $e->getMessage()]);
                        $this->addFlash('error', 'An error occurred during registration. Please try again.');
                    }
                } else if ($form->isSubmitted() && !$form->isValid()) {
                    // Handle validation errors for Ajax
                    if ($request->isXmlHttpRequest()) {
                        $errors = [];
                        foreach ($form->getErrors(true) as $error) {
                            $errors[] = $error->getMessage();
                        }
                        
                        // Get field-specific errors to display them properly
                        $fieldErrors = [];
                        foreach ($form->all() as $fieldName => $formField) {
                            if ($formField->getErrors()->count() > 0) {
                                $fieldErrors[$fieldName] = [];
                                foreach ($formField->getErrors() as $error) {
                                    $fieldErrors[$fieldName][] = $error->getMessage();
                                }
                            }
                        }
                        
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Please correct the errors in the form.',
                            'errors' => $errors,
                            'fieldErrors' => $fieldErrors
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
                
                return $this->render('front/loginSignup.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            } catch (\Exception $e) {
                // Log the detailed error information
                $this->logger->error('Registration form handling error: {message}', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                // Global exception handling
                $errorMessage = 'An error occurred. ' . ($this->getParameter('kernel.debug') ? $e->getMessage() : 'Please try again later.');
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $errorMessage
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                
                $this->addFlash('error', $errorMessage);
                
                // In case of a severe error that prevents normal form creation, create a new empty form
                if (!isset($form) || !$form) {
                    $user = new User();
                    $form = $this->createForm(RegistrationFormType::class, $user);
                }
                
                return $this->render('front/loginSignup.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
        } catch (\Exception $e) {
            // Log the detailed error information
            $this->logger->error('Registration form handling error: {message}', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Global exception handling
            $errorMessage = 'An error occurred. ' . ($this->getParameter('kernel.debug') ? $e->getMessage() : 'Please try again later.');
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $errorMessage
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            $this->addFlash('error', $errorMessage);
            
            // In case of a severe error that prevents normal form creation, create a new empty form
            if (!isset($form) || !$form) {
                $user = new User();
                $form = $this->createForm(RegistrationFormType::class, $user);
            }
            
            return $this->render('front/loginSignup.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        }
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function apiRegister(Request $request, UserRepository $userRepository): JsonResponse
    {
        try {
            // Check if the request is properly formatted as JSON
            if ($request->getContentType() !== 'json') {
                return $this->json(
                    ['success' => false, 'error' => 'Invalid content type. Expected JSON.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['success' => false, 'error' => 'Invalid JSON payload: ' . json_last_error_msg()],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Check required fields
            $requiredFields = ['firstName', 'lastName', 'email', 'password', 'phone_number', 'gender'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return $this->json(
                    [
                        'success' => false, 
                        'error' => "Missing required fields: " . implode(', ', $missingFields)
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Check if email already exists
            if ($userRepository->findOneBy(['email' => $data['email']])) {
                return $this->json(
                    ['success' => false, 'error' => 'Email is already registered'],
                    Response::HTTP_CONFLICT
                );
            }

            // Create new user
            $user = new User();
            $user->setName($data['firstName'] . ' ' . $data['lastName']);
            $user->setEmail($data['email']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
            $user->setPhoneNumber($data['phone_number']);
            
            // Handle gender enum
            try {
                $gender = \App\Enum\GENDER::from($data['gender']);
                $user->setGender($gender);
            } catch (\ValueError $e) {
                return $this->json(
                    [
                        'success' => false,
                        'error' => 'Invalid gender value. Expected one of: ' . implode(', ', array_column(\App\Enum\GENDER::cases(), 'name'))
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            // Set default values
            try {
                $user->setRole(\App\Enum\ROLE::CLIENT);
                $user->setAccountStatus(\App\Enum\ACCOUNT_STATUS::ACTIVE);
                $user->setStatus(\App\Enum\STATUS::OFFLINE);
                $user->setIsVerified(false);
            } catch (\ValueError $e) {
                $this->logger->error('Error setting enum values: {message}', ['message' => $e->getMessage()]);
                throw new \InvalidArgumentException('Invalid enum value: ' . $e->getMessage());
            }   
            
            // Set optional fields if provided
            if (isset($data['date_of_birth']) && !empty($data['date_of_birth'])) {
                try {
                    $user->setDateOfBirth(new \DateTime($data['date_of_birth']));
                } catch (\Exception $e) {
                    return $this->json(
                        [
                            'success' => false,
                            'error' => 'Invalid date format for date_of_birth. Expected format: YYYY-MM-DD'
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
            
            if (isset($data['profile_picture'])) {
                $user->setProfilePicture($data['profile_picture']);
            }

            // Save user
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $this->json(
                    [
                        'success' => true,
                        'message' => 'User registered successfully',
                        'user' => $this->serializer->serialize($user, 'json', ['groups' => 'user:read'])
                    ],
                    Response::HTTP_CREATED
                );
            } catch (\Exception $e) {
                return $this->json(
                    [
                        'success' => false,
                        'error' => 'Failed to save user: ' . $e->getMessage()
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

        } catch (\Exception $e) {
            return $this->json(
                [
                    'success' => false,
                    'error' => 'Registration failed: ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/debug-registration', name: 'app_debug_register', methods: ['GET'])]
    public function debugRegister(Request $request): Response
    {
        try {
            $output = [];
            
            // Environment information
            $output['environment'] = [
                'php_version' => PHP_VERSION,
                'symfony_environment' => $this->getParameter('kernel.environment'),
                'debug_mode' => $this->getParameter('kernel.debug'),
            ];
            
            // Create a test form to see if it works
            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            
            // Form field information
            $output['form_fields'] = [];
            foreach ($form as $field) {
                $output['form_fields'][$field->getName()] = [
                    'type' => get_class($field->getConfig()->getType()->getInnerType()),
                    'required' => $field->getConfig()->getRequired(),
                    'mapped' => $field->getConfig()->getMapped(),
                ];
            }
            
            // Check database connection 
            try {
                $connection = $this->entityManager->getConnection();
                $connection->connect();
                $output['database'] = [
                    'connected' => $connection->isConnected(),
                    'driver' => $connection->getDriver()->getName(),
                ];
            } catch (\Exception $e) {
                $output['database'] = [
                    'connected' => false,
                    'error' => $e->getMessage(),
                ];
            }
            
            // Return debug info as JSON
            return new JsonResponse($output);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
