<?php

namespace App\Controller\UserModule;

use App\Form\UserModule\ForgotPasswordStep1Type;
use App\Form\UserModule\ForgotPasswordStep2Type;
use App\Form\UserModule\ForgotPasswordStep3Type;
use App\Service\UserModule\UserEmailService;
use App\Service\UserModule\UserService;
use App\Service\UserModule\ValidationService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    private UserService $userService;
    private UserEmailService $userEmailService;
    private ValidationService $validationService;
    private LoggerInterface $logger;

    public function __construct(
        UserService $userService,
        UserEmailService $userEmailService,
        ValidationService $validationService,
        LoggerInterface $logger
    ) {
        $this->userService = $userService;
        $this->userEmailService = $userEmailService;
        $this->validationService = $validationService;
        $this->logger = $logger;
    }

    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        $form = $this->createForm(ForgotPasswordStep1Type::class, null, [
            'email_exists_callback' => fn($email) => $this->validationService->isEmailExists($email),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            $session = $request->getSession();
            $session->set('forgot_password_email', $email);

            $cache = new FilesystemAdapter();
            $verificationCode = random_int(100000, 999999);
            $sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);
            $cacheKey = "forgot_password_$sanitizedEmail";
            $cache->save($cache->getItem($cacheKey)->set($verificationCode)->expiresAfter(300));

            $this->userEmailService->sendEmail(
                $email,
                'Password Reset Verification Code',
                "Your verification code is: $verificationCode"
            );

            return $this->redirectToRoute('forgot_password_step', ['step' => 2]);
        }

        return $this->render('auth/forgot_password.html.twig', [
            'step' => 1,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forgot-password/step/{step}', name: 'forgot_password_step')]
    public function handleForgotPasswordStep(Request $request, int $step): Response
    {
        if ($step === 2) {
            $session = $request->getSession();
            $email = $session->get('forgot_password_email');

            if (!$email) {
                $this->logger->error('Email is missing in the session.');
                return $this->redirectToRoute('forgot_password');
            }

            $form = $this->createForm(ForgotPasswordStep2Type::class, null, [
                'email' => $email,
            ]);
            $form->handleRequest($request);
            $this->logger->info('Request Data: ' . json_encode($request->request->all()));

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $code = $data['code'];

                // Retrieve email from the session instead of the request
                $session = $request->getSession();
                $email = $session->get('forgot_password_email');

                if (!$email) {
                    return $this->json(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
                }

                $cache = new FilesystemAdapter();
                $sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);
                $cacheKey = "forgot_password_$sanitizedEmail";

                // Retrieve the cached code
                $cachedCode = $cache->getItem($cacheKey)->get();

                // Log for debugging
                $this->logger->info("Submitted Code: $code");
                $this->logger->info("Cached Code: $cachedCode");

                // Validate the code
                if (!$cachedCode || (string)$cachedCode !== (string)$code) {
                    return $this->json(['error' => 'Invalid verification code'], Response::HTTP_BAD_REQUEST);
                }

                return $this->redirectToRoute('forgot_password_step', ['step' => 3, 'email' => $email]);
            }
            return $this->render('auth/forgot_password.html.twig', [
                'step' => 2,
                'form' => $form->createView(),
            ]);
        }

        if ($step === 3) {
            $session = $request->getSession();
            $email = $session->get('forgot_password_email'); // Retrieve email from session

            if (!$email) {
                $this->logger->error('Email is missing in the session.');
                return $this->redirectToRoute('forgot_password');
            }

            $form = $this->createForm(ForgotPasswordStep3Type::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $password = $data['password'];

                $this->userService->updatePasswordByEmail($email, $password);

                $this->userEmailService->sendEmail(
                    $email,
                    'Password Changed',
                    'Your password has been successfully changed.'
                );

                return $this->redirectToRoute('login');
            }

            return $this->render('auth/forgot_password.html.twig', [
                'step' => 3,
                'form' => $form->createView(),
            ]);
        }

        return $this->redirectToRoute('forgot_password');
    }

    private function validateCode(string $code): bool
    {
        $cache = new FilesystemAdapter();
        $cachedCode = $cache->getItem("forgot_password_$code")->get();

        return $cachedCode && (string)$cachedCode === (string)$code;
    }
}