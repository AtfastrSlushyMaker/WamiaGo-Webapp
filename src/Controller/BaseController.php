<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class BaseController extends AbstractController
{
    private $requestStack;
    private $security;
    
    public function __construct(RequestStack $requestStack, Security $security)
    {
        $this->requestStack = $requestStack;
        $this->security = $security;
    }
    
    /**
     * Override the render method to ensure app.user and app.request are available
     */
    public function render(string $view, array $parameters = [], Response $response = null): Response
    {
        // Get the current request
        $request = $this->requestStack->getCurrentRequest();
        
        // Get actual authenticated user if available
        $user = $this->security->getUser();
        
        // Only use mock user if no actual user is authenticated
        if (!$user) {
            $user = (object)[
                'name' => 'Test User',
                'email' => 'test@example.com',
                'profilePicture' => null,
                'roles' => ['ROLE_USER']
            ];
        }
        
        // Add app data to parameters if it doesn't exist
        if (!isset($parameters['app'])) {
            $parameters['app'] = [
                'user' => $user,
                'request' => $request,
                'debug' => true,
                'environment' => 'dev'
            ];
        } else {
            // If app is already set, ensure user and request are available
            if (!isset($parameters['app']['user'])) {
                $parameters['app']['user'] = $user;
            }
            if (!isset($parameters['app']['request'])) {
                $parameters['app']['request'] = $request;
            }
        }
        
        // Call parent render method
        return parent::render($view, $parameters, $response);
    }
} 