<?php

namespace App\Controller\UserModule;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        // Check if the user is already logged in
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('user_dashboard');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();



        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): never
    {
        // Empty method - Symfony intercepts this route
        throw new \LogicException('This should never be reached!');
    }

    #[Route('/tosignup', name: 'tosignup')]
    public function register(): Response
    {
        // Empty method - Symfony intercepts this route
        return $this->redirectToRoute('signup');
    }
}