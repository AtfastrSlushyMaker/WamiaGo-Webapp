<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Repository\UserRepository;

class ProfileController extends AbstractController
{
    private Security $security;
    private UserRepository $userRepository;
    
    public function __construct(Security $security, UserRepository $userRepository) 
    {
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $profileCompletion = $this->calculateProfileCompletionPercentage($user);
        
        return $this->render('front/profile.html.twig', [
            'user' => $user,
            'profileCompletion' => $profileCompletion
        ]);
    }
    
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(): Response
    {
        // You'll implement this method for profile editing
        return $this->render('front/profile_edit.html.twig', [
            'user' => $this->security->getUser()
        ]);
    }
    
    private function calculateProfileCompletionPercentage($user): int
    {
        $completion = 0;
        
      
        if ($user->getProfilePicture()) {
            $completion += 30;
        }
        
    
        if ($user->isVerified()) {
            $completion += 20;
        }
        
        $completion += $user->getName() ? 4 : 0;
        $completion += $user->getEmail() ? 4 : 0;
        $completion += $user->getPhoneNumber() ? 4 : 0;
        $completion += $user->getDateOfBirth() ? 4 : 0;
        $completion += $user->getLocation() ? 4 : 0;
        
    
        
        return min($completion, 100);
    }
}