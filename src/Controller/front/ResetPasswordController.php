<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Service\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordService $resetPasswordService,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $email = $form->get('email')->getData();
                $this->resetPasswordService->processForgotPasswordRequest($email);

                $this->addFlash('success', 'If an account exists with this email, you will receive a password reset link shortly.');
                return $this->redirectToRoute('app_login');
            } catch (UserNotFoundException $e) {
                // Don't reveal whether a user account was found or not
                $this->addFlash('success', 'If an account exists with this email, you will receive a password reset link shortly.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while processing your request.');
            }
        }

        return $this->render('front/reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token): Response
    {
        try {
            $user = $this->resetPasswordService->validateResetToken($token);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Invalid or expired password reset link.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the new password
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
} 