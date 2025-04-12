<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginSignupController extends AbstractController
{
    private $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Enhanced error handling for AJAX requests
        if ($request->isXmlHttpRequest()) {
            if ($error) {
                $errorMessage = $error->getMessage();
                $errorKey = 'security.authentication_error';
                
                // Map cryptic security errors to user-friendly messages
                $errorMap = [
                    'Invalid credentials.' => 'The email or password you entered is incorrect.',
                    'User account is disabled.' => 'Your account has been disabled. Please contact support.',
                    'User account is locked.' => 'Your account has been locked due to too many failed login attempts.'
                ];
                
                $userMessage = isset($errorMap[$errorMessage]) ? $errorMap[$errorMessage] : 'Login failed. Please check your credentials and try again.';
                
                error_log('Login error: ' . $errorMessage);
                
                return $this->json([
                    'success' => false,
                    'message' => $userMessage,
                    'error_code' => $errorKey,
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

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Controller can be blank: it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}