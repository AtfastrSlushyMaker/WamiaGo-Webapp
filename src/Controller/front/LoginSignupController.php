<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\FormInterface;

class LoginSignupController extends AbstractController
{
    private $session;
    private $userRepository;

    public function __construct(
        RequestStack $requestStack,
        UserRepository $userRepository
    ) {
        $this->session = $requestStack->getSession();
        $this->userRepository = $userRepository;
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($request->isXmlHttpRequest()) {
            if ($error) {
                $errorMessage = $error->getMessage();
                $errorKey = 'security.authentication_error';
                $field = null;
                
                // Mapping of error messages to user-friendly messages
                $errorMap = [
                    'Invalid credentials.' => [
                        'message' => 'The email or password you entered is incorrect.',
                        'code' => 'invalid_credentials'
                    ],
                    'User account is disabled.' => [
                        'message' => 'Your account has been disabled. Please contact support.',
                        'code' => 'account_disabled'
                    ],
                    'User account is locked.' => [
                        'message' => 'Your account has been locked due to too many failed login attempts.',
                        'code' => 'account_locked'
                    ],
                    'Please enter a valid email address.' => [
                        'message' => 'Please enter a valid email address in the format name@example.com',
                        'code' => 'invalid_email_format',
                        'field' => 'email'
                    ],
                    'Email cannot be empty.' => [
                        'message' => 'Please enter your email address',
                        'code' => 'empty_email',
                        'field' => 'email'
                    ],
                    'Password cannot be empty.' => [
                        'message' => 'Please enter your password',
                        'code' => 'empty_password',
                        'field' => 'password'
                    ],
                    'No account found with this email address.' => [
                        'message' => 'No account found with this email address. Please check your spelling or create a new account.',
                        'code' => 'email_not_found',
                        'field' => 'email'
                    ],
                    'The password you entered is incorrect.' => [
                        'message' => 'The password you entered is incorrect. Please try again or use the forgot password option.',
                        'code' => 'invalid_password',
                        'field' => 'password'
                    ],
                    'Too many failed login attempts.' => [
                        'message' => 'Too many failed login attempts. For security reasons, please wait before trying again.',
                        'code' => 'too_many_attempts'
                    ]
                ];
                
                // Determine the appropriate error info
                $errorInfo = null;
                
                // Try exact match first
                if (isset($errorMap[$errorMessage])) {
                    $errorInfo = $errorMap[$errorMessage];
                } else {
                    // Try partial match
                    foreach ($errorMap as $key => $info) {
                        if (strpos($errorMessage, $key) !== false) {
                            $errorInfo = $info;
                            break;
                        }
                    }
                }
                
                // Default fallback if no match found
                if (!$errorInfo) {
                    $errorInfo = [
                        'message' => 'Login failed. Please check your credentials and try again.',
                        'code' => 'authentication_error'
                    ];
                }
                
                return $this->json([
                    'success' => false,
                    'message' => $errorInfo['message'],
                    'error_code' => $errorInfo['code'],
                    'field' => isset($errorInfo['field']) ? $errorInfo['field'] : null,
                    'technical_details' => $this->getParameter('kernel.debug') ? $errorMessage : null
                ], Response::HTTP_UNAUTHORIZED);
            }
          
            return $this->json([
                'success' => false,
                'message' => 'An unexpected error occurred during login.',
                'error_code' => 'unexpected_error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Check if we need to show a specific panel
        $showLoginPanel = true; // Default to showing login panel
        if ($request->query->has('panel')) {
            $panel = $request->query->get('panel');
            $showLoginPanel = ($panel === 'login');
        }

        // Create registration form for the combined template
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        return $this->render('front/loginSignup.html.twig', [
            'registrationForm' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error,
            'show_login_panel' => $showLoginPanel,
        ]);
    }

    #[Route('/login-signup', name: 'app_login_signup', methods: ['GET'])]
    public function loginSignup(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Check if we need to show a specific panel
        $showLoginPanel = true; // Default to showing login panel
        if ($request->query->has('panel')) {
            $panel = $request->query->get('panel');
            $showLoginPanel = ($panel === 'login');
        }

        // Create registration form for the combined template
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        return $this->render('front/loginSignup.html.twig', [
            'registrationForm' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error,
            'show_login_panel' => $showLoginPanel,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(Request $request): Response
    {
        // This should never be executed as Symfony will intercept the logout request
        // But just in case it does get called, provide a fallback redirect
        return $this->redirectToRoute('app_login');
    }

    #[Route('/validate-step/{step}', name: 'app_validate_registration_step', methods: ['POST'])]
    public function validateRegistrationStep(Request $request, int $step): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $errors = [];
        $fieldErrors = [];

        // Validate specific fields based on step
        switch ($step) {
            case 1:
                // Validate email and name fields
                $this->validateStep1Fields($form, $fieldErrors);
                break;
            case 2:
                // Validate personal details
                $this->validateStep2Fields($form, $fieldErrors);
                break;
            case 3:
                // Validate password and terms
                $this->validateStep3Fields($form, $fieldErrors);
                break;
            default:
                return new JsonResponse(['success' => false, 'message' => 'Invalid step'], 400);
        }

        if (count($fieldErrors) > 0) {
            return new JsonResponse([
                'success' => false,
                'fieldErrors' => $fieldErrors
            ]);
        }

        return new JsonResponse(['success' => true]);
    }

    private function validateStep1Fields(FormInterface $form, array &$fieldErrors): void
    {
        $email = $form->get('email')->getData();
        if ($email) {
            // Check if email already exists
            $existingUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                $fieldErrors['email'][] = 'This email is already registered.';
            }
        }

        // Validate first name
        $firstName = $form->get('firstName')->getData();
        if ($firstName && !preg_match('/^[a-zA-Z\s\'-]{2,50}$/', $firstName)) {
            $fieldErrors['firstName'][] = 'First name must be between 2-50 characters and contain only letters.';
        }

        // Validate last name
        $lastName = $form->get('lastName')->getData();
        if ($lastName && !preg_match('/^[a-zA-Z\s\'-]{2,50}$/', $lastName)) {
            $fieldErrors['lastName'][] = 'Last name must be between 2-50 characters and contain only letters.';
        }
    }

    private function validateStep2Fields(FormInterface $form, array &$fieldErrors): void
    {
        // Validate phone number
        $phone = $form->get('phone_number')->getData();
        if ($phone && !preg_match('/^\+?[1-9][0-9]{7,14}$/', $phone)) {
            $fieldErrors['phone_number'][] = 'Please enter a valid international phone number.';
        }

        // Validate date of birth
        $dob = $form->get('dateOfBirth')->getData();
        if ($dob) {
            $age = $dob->diff(new \DateTime())->y;
            if ($age > 120 || $dob > new \DateTime()) {
                $fieldErrors['dateOfBirth'][] = 'Please enter a valid date of birth.';
            }
        }

        // Validate location
        $location = $form->get('location')->getData();
        if (!$location) {
            $fieldErrors['location'][] = 'Location is required.';
        }

        // Validate gender
        $gender = $form->get('gender')->getData();
        if (!$gender) {
            $fieldErrors['gender'][] = 'Please select your gender.';
        }
    }

    private function validateStep3Fields(FormInterface $form, array &$fieldErrors): void
    {
        $password = $form->get('plainPassword')->getData();
        
        if ($password) {
            if (strlen($password) < 8) {
                $fieldErrors['plainPassword'][] = 'Password must be at least 8 characters long.';
            }
            
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]/', $password)) {
                $fieldErrors['plainPassword'][] = 'Password must include at least one letter, one number, and one special character.';
            }
        }
    }
}