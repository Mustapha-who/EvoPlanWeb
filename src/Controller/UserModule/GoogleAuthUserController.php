<?php

namespace App\Controller\UserModule;
use App\Entity\UserModule\User;
use App\Service\UserModule\GoogleAuthenticator;
use App\Service\UserModule\GoogleAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Google\Client as GoogleClient;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class GoogleAuthUserController extends AbstractController
{
    private GoogleAuthService $googleAuthService;

    public function __construct(GoogleAuthService $googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
    }

    #[Route('/google/login', name: 'google_login')]
    public function login(): Response
    {
        $authUrl = $this->googleAuthService->getAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/google/callback', name: 'google_callback')]
    public function callback(
        Request $request,
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        GoogleAuthenticator $authenticator
    ): Response {
        $authCode = $request->get('code');

        if (!$authCode) {
            $this->addFlash('error', 'Missing authorization code.');
            return $this->redirectToRoute('login');
        }

        try {
            $accessToken = $this->googleAuthService->fetchAccessToken($authCode);
            $googleUser = $this->googleAuthService->verifyIdToken($accessToken['id_token']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Google authentication failed: ' . $e->getMessage());
            return $this->redirectToRoute('login');
        }

        $email = $googleUser['email'];

        // Check if the user exists in the database
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            // Authenticate the user using Symfony's security system
            return $userAuthenticator->authenticateUser($user, $authenticator, $request);
        }

        // Redirect to the Google-specific signup page with the email pre-filled
        return $this->redirectToRoute('google_signup', ['email' => $email]);
    }
}