<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Repository\LocationRepository;
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
use Symfony\Component\Form\FormInterface;

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

    /**
     * Validates a specific step of the registration form
     */
    private function validateStep(FormInterface $form, int $step): array
    {
        $errors = [];
        $fieldErrors = [];

        $stepFields = [
            1 => ['email', 'firstName', 'lastName'],
            2 => ['phone_number', 'dateOfBirth', 'location', 'gender'],
            3 => ['plainPassword']
        ];

        if (!isset($stepFields[$step])) {
            return ['success' => false, 'message' => 'Invalid step'];
        }

        $this->logger->info('Validating step {step} with fields: {fields}', [
            'step' => $step,
            'fields' => implode(', ', $stepFields[$step])
        ]);

        // For step 1, combine firstName and lastName into name
        if ($step === 1) {
            $firstName = $form->get('firstName')->getData();
            $lastName = $form->get('lastName')->getData();
            if ($firstName && $lastName) {
                $user = $form->getData();
                $user->setName($firstName . ' ' . $lastName);
            }
        }

        foreach ($stepFields[$step] as $fieldName) {
            if (!$form->has($fieldName)) {
                $this->logger->warning('Field {field} not found in form', ['field' => $fieldName]);
                continue;
            }

            $field = $form->get($fieldName);
            $value = $field->getData();
              // Special handling for date and gender fields
            if ($fieldName === 'dateOfBirth') {
                $this->logger->info('Date of birth field details:', [
                    'raw_value' => $value,
                    'value_type' => is_object($value) ? get_class($value) : gettype($value),
                    'view_data' => $field->getViewData(),
                    'is_valid' => $field->isValid(),
                    'errors' => $field->getErrors()->count()
                ]);
            }
            
            // Debug gender field
            if ($fieldName === 'gender') {
                $this->logger->info('Gender field details:', [
                    'raw_value' => $value,
                    'value_type' => is_object($value) ? get_class($value) : gettype($value),
                    'view_data' => $field->getViewData(),
                    'is_valid' => $field->isValid(),
                    'errors' => $field->getErrors()->count()
                ]);
            }

            $this->logger->info('Field {field} value: {value}', [
                'field' => $fieldName,
                'value' => is_object($value) ? get_class($value) : $value
            ]);

            if ($field->getErrors()->count() > 0) {
                $fieldErrors[$fieldName] = [];
                foreach ($field->getErrors() as $error) {
                    $fieldErrors[$fieldName][] = $error->getMessage();
                    $this->logger->warning('Field {field} error: {error}', [
                        'field' => $fieldName,
                        'error' => $error->getMessage()
                    ]);
                }
            }
        }

        // For step 1, validate the combined name field
        if ($step === 1) {
            $user = $form->getData();
            if (!$user->getName()) {
                $fieldErrors['name'] = ['Name cannot be blank'];
            }
        }

        return [
            'success' => empty($fieldErrors),
            'fieldErrors' => $fieldErrors
        ];
    }

    #[Route('/validate-step/{step}', name: 'app_validate_step', methods: ['POST'])]
    /**
     * @Route("/debug-form", name="app_debug_form")
     */
    public function debugForm(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        
        try {
            $form->handleRequest($request);
            
            // Get form data
            $formData = $request->request->all();
            
            // Prepare debug output
            $output = [
                'form_submitted' => $form->isSubmitted(),
                'form_valid' => $form->isValid(),
                'request_data' => $formData,
                'form_errors' => []
            ];
            
            if ($form->isSubmitted()) {
                // Get all form errors
                foreach ($form->getErrors(true) as $error) {
                    $output['form_errors'][] = [
                        'field' => $error->getOrigin()->getName(),
                        'message' => $error->getMessage()
                    ];
                }
            }
            
            return new JsonResponse($output);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

/**
     * @Route("/validate-step/{step}", name="app_validate_registration_step", methods={"POST"})
     */
public function validateRegistrationStep(Request $request, int $step): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        
        try {
            $form->handleRequest($request);
            
            if ($form->isSubmitted()) {
                $this->logger->info('Form submitted for step {step}', ['step' => $step]);
                
                // Log form data
                $formData = $request->request->all();
                $this->logger->info('Form data: {data}', ['data' => json_encode($formData)]);
                
                // Set name and password before validation for step 1
                if ($step === 1) {
                    $firstName = $form->get('firstName')->getData();
                    $lastName = $form->get('lastName')->getData();
                    if ($firstName && $lastName) {
                        $user->setName($firstName . ' ' . $lastName);
                    }
                }
                
                // Set password before validation for step 3
                if ($step === 3) {
                    $plainPassword = $form->get('plainPassword')->getData();
                    if ($plainPassword) {
                        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                        $user->setPassword($hashedPassword);
                    }
                }
                  // Special handling for gender field in step 2
                if ($step === 2) {
                    try {
                        $formData = $request->request->all();
                        
                        // Check if gender was submitted as a string
                        if (isset($formData['registration_form']['gender']) && is_string($formData['registration_form']['gender'])) {
                            $genderValue = $formData['registration_form']['gender'];
                            
                            // Try to convert the string to a GENDER enum
                            try {
                                $genderEnum = \App\Enum\GENDER::from($genderValue);
                                $user->setGender($genderEnum);
                                $this->logger->info('Successfully converted gender string {gender} to enum', [
                                    'gender' => $genderValue
                                ]);
                            } catch (\ValueError $e) {
                                $this->logger->error('Failed to convert gender string {gender} to enum: {error}', [
                                    'gender' => $genderValue,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Error processing gender field: {error}', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Re-submit the form with the updated user entity
                $form = $this->createForm(RegistrationFormType::class, $user);
                $form->handleRequest($request);
                
                $validation = $this->validateStep($form, $step);
                
                if (!$validation['success']) {
                    $this->logger->warning('Validation failed for step {step}: {errors}', [
                        'step' => $step,
                        'errors' => json_encode($validation['fieldErrors'])
                    ]);
                    
                    return $this->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'fieldErrors' => $validation['fieldErrors']
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                $this->logger->info('Step {step} validated successfully', ['step' => $step]);
                return $this->json([
                    'success' => true,
                    'message' => 'Step validated successfully'
                ]);
            }
            
            $this->logger->warning('Form not submitted for step {step}', ['step' => $step]);
            return $this->json([
                'success' => false,
                'message' => 'Form not submitted'
            ], Response::HTTP_BAD_REQUEST);
            
        } catch (\Exception $e) {
            $this->logger->error('Validation error: {message}', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'An error occurred during validation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserRepository $userRepository, LocationRepository $locationRepository): Response
    {
        try {
            if ($this->getUser()) {
                return $this->redirectToRoute('app_front_home');
            }

            $user = new User();
            $user->setRole(ROLE::CLIENT);
            
            $form = $this->createForm(RegistrationFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $this->logger->info('Registration form submitted with data: {data}', [
                    'data' => json_encode($request->request->all())
                ]);

                // Set name and password before validation
                $firstName = $form->get('firstName')->getData();
                $lastName = $form->get('lastName')->getData();
                if ($firstName && $lastName) {
                    $user->setName($firstName . ' ' . $lastName);
                }

                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                // Re-submit the form with the updated user entity
                $form = $this->createForm(RegistrationFormType::class, $user);
                $form->handleRequest($request);

                if (!$form->isValid()) {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[] = $error->getMessage();
                    }
                    
                    $fieldErrors = [];
                    foreach ($form->all() as $child) {
                        if (!$child->isValid()) {
                            foreach ($child->getErrors() as $error) {
                                $fieldErrors[$child->getName()] = $error->getMessage();
                            }
                        }
                    }
                    
                    $this->logger->warning('Form validation failed: {errors}', [
                        'errors' => json_encode($errors),
                        'fieldErrors' => json_encode($fieldErrors)
                    ]);
                    
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => $errors,
                            'fieldErrors' => $fieldErrors
                        ], Response::HTTP_BAD_REQUEST);
                    }
                    
                    $this->addFlash('error', implode(', ', $errors));
                    return $this->render('front/loginSignup.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                try {
                    // Get the form data
                    $user = $form->getData();
                    
                    // Check if email already exists
                    $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
                    if ($existingUser) {
                        $this->logger->warning('Registration failed: Email already exists', [
                            'email' => $user->getEmail()
                        ]);
                        
                        if ($request->isXmlHttpRequest()) {
                            return $this->json([
                                'success' => false,
                                'message' => 'Email already exists',
                                'fieldErrors' => ['email' => 'This email is already registered']
                            ], Response::HTTP_BAD_REQUEST);
                        }
                        
                        $this->addFlash('error', 'This email is already registered');
                        return $this->render('front/loginSignup.html.twig', [
                            'registrationForm' => $form->createView(),
                        ]);
                    }

                    // Check if phone number already exists
                    $existingUser = $userRepository->findOneBy(['phone_number' => $user->getPhoneNumber()]);
                    if ($existingUser) {
                        if ($request->isXmlHttpRequest()) {
                            return $this->json([
                                'success' => false,
                                'message' => 'Phone number already exists',
                                'fieldErrors' => ['phone_number' => 'This phone number is already registered']
                            ], Response::HTTP_BAD_REQUEST);
                        }
                        $this->addFlash('error', 'This phone number is already registered');
                        return $this->render('front/loginSignup.html.twig', [
                            'registrationForm' => $form->createView(),
                        ]);
                    }

                    // Set default values
                    $user->setAccountStatus(\App\Enum\ACCOUNT_STATUS::ACTIVE);
                    $user->setStatus(\App\Enum\STATUS::OFFLINE);
                    $user->setIsVerified(false);
                    
                    // Save the user
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    
                    $this->logger->info('User registered successfully: {email}', [
                        'email' => $user->getEmail()
                    ]);
                    
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => true,
                            'message' => 'Registration successful!',
                            'redirect' => $this->generateUrl('app_login')
                        ]);
                    }
                    
                    $this->addFlash('success', 'Registration successful! You can now login.');
                    return $this->redirectToRoute('app_login');
                } catch (\Exception $e) {
                    $this->logger->error('Registration error: {message}', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => false,
                            'message' => 'An error occurred during registration: ' . $e->getMessage()
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                    
                    $this->addFlash('error', 'An error occurred during registration: ' . $e->getMessage());
                }
            }

            return $this->render('front/loginSignup.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Registration form handling error: {message}', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = 'An error occurred. ' . ($this->getParameter('kernel.debug') ? $e->getMessage() : 'Please try again later.');
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => $errorMessage
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            $this->addFlash('error', $errorMessage);
            return $this->render('front/loginSignup.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        }
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function apiRegister(Request $request, UserRepository $userRepository): JsonResponse
    {
        try {
            
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

        
            if ($userRepository->findOneBy(['email' => $data['email']])) {
                return $this->json(
                    ['success' => false, 'error' => 'Email is already registered'],
                    Response::HTTP_CONFLICT
                );
            }

   
            $user = new User();
            $user->setName($data['firstName'] . ' ' . $data['lastName']);
            $user->setEmail($data['email']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
            $user->setPhoneNumber($data['phone_number']);
            
       
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
            
        
            try {
                $user->setRole(\App\Enum\ROLE::CLIENT);
                $user->setAccountStatus(\App\Enum\ACCOUNT_STATUS::ACTIVE);
                $user->setStatus(\App\Enum\STATUS::OFFLINE);
                $user->setIsVerified(false);
            } catch (\ValueError $e) {
                $this->logger->error('Error setting enum values: {message}', ['message' => $e->getMessage()]);
                throw new \InvalidArgumentException('Invalid enum value: ' . $e->getMessage());
            }   
            
           
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
            
         
            $output['environment'] = [
                'php_version' => PHP_VERSION,
                'symfony_environment' => $this->getParameter('kernel.environment'),
                'debug_mode' => $this->getParameter('kernel.debug'),
            ];
            

            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            
        
            $output['form_fields'] = [];
            foreach ($form as $field) {
                $output['form_fields'][$field->getName()] = [
                    'type' => get_class($field->getConfig()->getType()->getInnerType()),
                    'required' => $field->getConfig()->getRequired(),
                    'mapped' => $field->getConfig()->getMapped(),
                ];
            }
            
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
