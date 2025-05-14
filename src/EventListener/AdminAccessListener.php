<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class AdminAccessListener
{
    private $security;
    private $urlGenerator;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // Skip for admin routes, login, logout, and assets
        if (strpos($path, '/admin') === 0 || 
            strpos($path, '/login') === 0 || 
            strpos($path, '/logout') === 0 ||
            strpos($path, '/_logout') === 0 ||
            strpos($path, '/reclamation') === 0 ||
            strpos($path, '/images') === 0 ||
            strpos($path, '/css') === 0 ||
            strpos($path, '/js') === 0 ||
            strpos($path, '/_') === 0) {
            return;
        }

        // Check if user is admin and redirect to dashboard
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('admin_dashboard')));
        }
    }
} 