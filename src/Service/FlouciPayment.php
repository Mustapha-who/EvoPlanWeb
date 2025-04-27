<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FlouciPayment
{
    private string $baseUrl = 'https://developers.flouci.com/api';

    public function __construct(
        private string $appToken,
        private string $appSecret,
        private HttpClientInterface $client
    ) {}

    public function generatePayment(
        int $amount,
        string $successLink,
        string $failLink,
        string $trackingId,
        bool $acceptCard = true,
        int $sessionTimeout = 1200
    ): array {
        $response = $this->client->request('POST', $this->baseUrl.'/generate_payment', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'app_token' => $this->appToken,
                'app_secret' => $this->appSecret,
                'amount' => $amount,
                'accept_card' => $acceptCard,
                'session_timeout_secs' => $sessionTimeout,
                'success_link' => $successLink,
                'fail_link' => $failLink,
                'developer_tracking_id' => $trackingId,
            ],
        ]);

        return $response->toArray();
    }

    public function verifyPayment(string $paymentId): array
    {
        $response = $this->client->request('GET', $this->baseUrl.'/verify_payment/'.$paymentId, [
            'headers' => [
                'Content-Type' => 'application/json',
                'apppublic' => $this->appToken,
                'appsecret' => $this->appSecret,
            ],
        ]);

        return $response->toArray();
    }
}