<?php

namespace App\Controller\UserModule;

use App\Service\UserModule\RecaptchaValidator;
use App\Service\UserModule\UserEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends AbstractController
{
    private UserEmailService $emailService;
    private RecaptchaValidator $recaptchaValidator;
    private string $siteKey;

    public function __construct(UserEmailService $emailService, RecaptchaValidator $recaptchaValidator,string $siteKey)
    {
        $this->emailService = $emailService;
        $this->recaptchaValidator = $recaptchaValidator;
        $this->siteKey = $siteKey;
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('user_dashboard');
        }


        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Check if the form was submitted
        if ($request->isMethod('POST')) {
            $recaptchaToken = $request->request->get('g-recaptcha-response');

            // Validate the reCAPTCHA token
            if (!$this->recaptchaValidator->validate($recaptchaToken)) {
                $error = 'Invalid reCAPTCHA. Please try again.';
                return $this->render('auth/login.html.twig', [
                    'last_username' => $lastUsername,
                    'error' => $error,
                    'site_key' => $this->siteKey,
                ]);
            }
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'site_key' => $this->siteKey,
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