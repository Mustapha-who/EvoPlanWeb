<?php

namespace App\Service\UserModule;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidator
{
    private HttpClientInterface $httpClient;
    private string $secretKey;

    public function __construct(HttpClientInterface $httpClient, string $secretKey)
    {
        $this->httpClient = $httpClient;
        $this->secretKey = $secretKey;
    }

    public function validate(string $token): bool
    {
        $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $token,
            ],
        ]);

        $data = $response->toArray();

        return $data['success'];
    }
}