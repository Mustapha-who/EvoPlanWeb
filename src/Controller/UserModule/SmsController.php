<?php

namespace App\Controller\UserModule;

use App\Service\UserModule\InfoBipSMS;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SmsController extends AbstractController
{
    private InfoBipSMS $smsService;

    public function __construct(InfoBipSMS $smsService)
    {
        $this->smsService = $smsService;
    }

    #[Route('/send-sms', name: 'send_sms')]
    public function sendSms(): JsonResponse
    {
        $phoneNumber = '21624964935'; // Replace with dynamic input
        $message = 'Your verification code is 123456.';

        $success = $this->smsService->sendSms($phoneNumber, $message);

        if ($success) {
            return new JsonResponse(['status' => 'success', 'message' => 'SMS sent successfully.']);
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Failed to send SMS.'], 500);
    }
}