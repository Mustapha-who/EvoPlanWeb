<?php

namespace App\Service\UserModule;

use App\Repository\UserModule\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class GoogleAuthenticator extends AbstractAuthenticator
{
    private GoogleAuthService $googleAuthService;
    private UserRepository $userRepository;
    private UrlGeneratorInterface $router;

    public function __construct
    (
        GoogleAuthService $googleAuthService,
        UserRepository $userRepository,
        UrlGeneratorInterface $router
    )
    {
        $this->googleAuthService = $googleAuthService;
        $this->userRepository = $userRepository;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'google_callback' && $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        $authCode = $request->query->get('code');

        if (!$authCode) {
            throw new AuthenticationException('Missing authorization code.');
        }

        try {
            $accessToken = $this->googleAuthService->fetchAccessToken($authCode);

            if (isset($accessToken['error'])) {
                throw new AuthenticationException('Invalid Google token: ' . $accessToken['error']);
            }

            $idToken = $accessToken['id_token'] ?? null;
            $userInfo = $this->googleAuthService->verifyIdToken($idToken);

            if (!$userInfo) {
                throw new AuthenticationException('Failed to verify ID token.');
            }

            $email = $userInfo['email'];

            return new SelfValidatingPassport(
                new UserBadge($email, function () use ($email) {
                    $user = $this->userRepository->findOneBy(['email' => $email]);
                    if (!$user) {
                        throw new AuthenticationException(json_encode(['redirect' => $this->router->generate('google_signup', ['email' => $email])]));
                    }
                    return $user;
                })
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Google authentication failed: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if ($user) {
            return new RedirectResponse('/userdash');
        }

        // Retrieve the email from the request attributes
        $email = $request->attributes->get('email');
        if ($email) {
            $signupUrl = $this->router->generate('google_signup', ['email' => $email]);
            return new RedirectResponse($signupUrl);
        }

        return new Response('Authentication failed: User not found.', Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessage();

        // Check if the exception contains a redirection URL
        if (str_contains($message, 'redirect')) {
            $data = json_decode($message, true);
            if (isset($data['redirect'])) {
                return new RedirectResponse($data['redirect']);
            }
        }

        return new Response('Authentication failed: ' . $message, Response::HTTP_UNAUTHORIZED);
    }
}