<?php

namespace App\Service\UserModule;
use Google\Client as GoogleClient;

class GoogleAuthService
{
    private GoogleClient $googleClient;

    public function __construct()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->googleClient->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $this->googleClient->addScope('email');
        $this->googleClient->addScope('profile');
    }

    public function getAuthUrl(): string
    {
        return $this->googleClient->createAuthUrl();
    }

    public function fetchAccessToken(string $authCode): array
    {
        return $this->googleClient->fetchAccessTokenWithAuthCode($authCode);
    }

    public function verifyIdToken(string $idToken): ?array
    {
        $payload = $this->googleClient->verifyIdToken($idToken);

        if (!$payload) {
            return null;
        }

        // Adjust for clock skew (e.g., 5 minutes)
        $currentTime = time();
        $allowedSkew = 300; // 5 minutes in seconds

        if (isset($payload['iat']) && $payload['iat'] > ($currentTime + $allowedSkew)) {
            throw new \Exception('Token issued at time is in the future.');
        }

        if (isset($payload['exp']) && $payload['exp'] < ($currentTime - $allowedSkew)) {
            throw new \Exception('Token has expired.');
        }

        return $payload;
    }
}