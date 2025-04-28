<?php

namespace App\Service\UserModule;

use App\Repository\UserModule\UserRepository;
use App\Service\UserModule\RecaptchaValidator;
use Illuminate\Contracts\Auth\UserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
class RecaptchaAuthenticator extends AbstractAuthenticator
{
    private RecaptchaValidator $recaptchaValidator;
    private UserProvider $userProvider;
    private UserRepository $userRepository;
    private PasswordEncryption $passwordEncryption;
    private LoggerInterface $logger;

    public function __construct(
        RecaptchaValidator $recaptchaValidator,
        UserRepository $userRepository,
        LoggerInterface $logger,
        PasswordEncryption $passwordEncryption)
    {
        $this->recaptchaValidator = $recaptchaValidator;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->passwordEncryption = $passwordEncryption;
    }

    public function supports(Request $request): bool
    {
        // Ensure this authenticator is only used for login POST requests
        return $request->isMethod('POST') && $request->getPathInfo() === '/login';
    }

    public function authenticate(Request $request): Passport
    {
        $this->logger->info('Authenticating request in RecaptchaAuthenticator.');

        $recaptchaToken = $request->request->get('g-recaptcha-response');
        $email = $request->request->get('_username');
        $password = $request->request->get('_password');

        // Log the request data
        $this->logger->info('Request data', [
            'recaptcha_token' => $recaptchaToken,
            'email' => $email,
        ]);

        if (!$email || !$password) {
            $this->logger->error('Email or password is missing in the request.');
            throw new CustomUserMessageAuthenticationException('Email and password are required.');
        }

        if (!$recaptchaToken || !$this->recaptchaValidator->validate($recaptchaToken)) {
            $this->logger->error('Invalid reCAPTCHA token.');
            throw new CustomUserMessageAuthenticationException('Invalid reCAPTCHA. Please try again.');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$this->passwordEncryption->verifyPassword($password,$user->getPassword())) {
            $this->logger->error('Invalid password.');
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // Allow the default success behavior
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        // Add the error message to the session
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());

        // Redirect back to the login page
        return new RedirectResponse('/login');
    }
}