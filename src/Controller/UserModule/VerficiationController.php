<?php

namespace App\Controller\UserModule;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use App\Service\UserModule\UserEmailService;
use App\Service\UserModule\InfoBipSMS;
use Symfony\Component\Routing\Annotation\Route;

class VerficiationController extends AbstractController
{
    private UserEmailService $emailService;
    private InfoBipSMS $smsService;
    private LoggerInterface $logger;
    public function __construct(UserEmailService $emailService, InfoBipSMS $smsService ,LoggerInterface $logger)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->logger = $logger;
    }
    #[Route('/send-email-verification-code', name: 'send_email_verification_code', methods: ['POST'])]
    public function sendEmailVerificationCode(Request $request): JsonResponse
    {
        $this->logger->info('sendEmailVerificationCode method called.');

        // Parse the JSON payload
        $data = $request->toArray();
        $newEmail = trim((string) ($data['email'] ?? ''));
        $user = $this->getUser();

        if (empty($newEmail)) {
            $this->logger->warning('Email is required but not provided.');
            return new JsonResponse(['success' => false, 'message' => 'Email is required.'], 400);
        }

        $code = random_int(100000, 999999);

        // Store the code in the cache
        $cache = new FilesystemAdapter();
        $cacheItem = $cache->getItem('email_verification_' . $user->getId());
        $cacheItem->set($code);
        $cacheItem->expiresAfter(300); // Code valid for 5 minutes
        $cache->save($cacheItem);


        // Send the code via email
        $this->logger->info('Sending email to: ' . $newEmail);
        $this->emailService->sendEmail(
            $newEmail,
            'Verification Code',
            "Your verification code is: $code"
        );
        $this->logger->info('Email sent successfully.');

        return new JsonResponse(['success' => true, 'message' => 'Verification code sent to email.']);
    }

    #[Route('/send-phone-verification-code', name: 'send_phone_verification_code', methods: ['POST'])]
    public function sendPhoneVerificationCode(Request $request): JsonResponse
    {
        $this->logger->info('sendPhoneVerificationCode method called.');

        $data = $request->toArray();
        $newPhone = trim((string) $data['phone'] ?? null);
        $user = $this->getUser();

        if (!$newPhone) {
            return new JsonResponse(['success' => false, 'message' => 'Phone number is required.'], 400);
        }

        // Add the default country code if missing
        $formattedPhone = '+216' . ltrim($newPhone, '0'); // Remove leading zero if present

        $code = random_int(100000, 999999);

        // Store the code in the cache
        $cache = new FilesystemAdapter();
        $cacheItem = $cache->getItem('phone_verification_' . $user->getId());
        $cacheItem->set($code);
        $cacheItem->expiresAfter(300); // Code valid for 5 minutes
        $cache->save($cacheItem);



        // Send the code via SMS
        $this->logger->info('Sending SMS to: ' . $formattedPhone);
        $this->smsService->sendSMS($formattedPhone, "Your verification code is: $code");
        $this->logger->info('SMS sent successfully.');

        return new JsonResponse(['success' => true, 'message' => 'Verification code sent to phone.']);
    }

    #[Route('/verify-email-code', name: 'verify_email_code', methods: ['POST'])]
    public function verifyEmailCode(Request $request): JsonResponse
    {
        $this->logger->info('verifyEmailCode method called.');

        // Parse the JSON payload
        $data = $request->toArray();
        $submittedCode = trim((string) ($data['code'] ?? ''));
        $this->logger->info('Submitted code: ' . ($submittedCode ?? 'null'));

        $user = $this->getUser();
        $this->logger->info('User ID: ' . $user->getId());

        // Retrieve the code from the cache
        $cache = new FilesystemAdapter();
        $cachedCode = trim((string) $cache->getItem('email_verification_' . $user->getId())->get());
        $this->logger->info('Cached code: ' . ($cachedCode ?? 'null'));

        if ($cachedCode && $submittedCode === $cachedCode) {
            $this->logger->info('Email verification successful.');
            return new JsonResponse(['success' => true, 'message' => 'Email verification successful.']);
        }

        $this->logger->warning('Invalid email verification code.');
        return new JsonResponse(['success' => false, 'message' => 'Invalid email verification code.'], 400);
    }

    #[Route('/verify-phone-code', name: 'verify_phone_code', methods: ['POST'])]
    public function verifyPhoneCode(Request $request): JsonResponse
    {
        $this->logger->info('verifyPhoneCode method called.');

        $data = $request->toArray();
        $submittedCode = trim((string) ($data['code'] ?? ''));
        if (empty($submittedCode)) {
            $this->logger->warning('No phone code submitted.');
            return new JsonResponse(['success' => false, 'message' => 'Verification code is required.'], 400);
        }
        $this->logger->info('Submitted code: ' . $submittedCode);

        $user = $this->getUser();
        $this->logger->info('User ID: ' . $user->getId());

        $cache = new FilesystemAdapter();
        $cacheKey = 'phone_verification_' . $user->getId();
        $cachedCode = trim((string) $cache->getItem($cacheKey)->get());
        $this->logger->info('Cached code: ' . $cachedCode);

        // Debugging the comparison
        $this->logger->info('Comparison result: ' . var_export($submittedCode === $cachedCode, true));

        if ($cachedCode && $submittedCode === $cachedCode) {
            $this->logger->info('Phone verification successful.');
            return new JsonResponse(['success' => true, 'message' => 'Phone verification successful.']);
        }

        $this->logger->warning('Invalid phone verification code.');
        return new JsonResponse(['success' => false, 'message' => 'Invalid phone verification code.'], 400);
    }
}