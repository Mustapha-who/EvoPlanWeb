<?php

namespace App\Service;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twilio\Rest\Client;

class TwilioService
{
    private $client;
    private $fromNumber;

    public function __construct(ParameterBagInterface $params)
    {
        $accountSid = $params->get('twilio_account_sid');
        $authToken = $params->get('twilio_auth_token');
        $this->fromNumber = $params->get('twilio_phone_number');
        $this->client = new Client($accountSid, $authToken);
    }

    public function sendSms(string $to, string $message): void
    {
        $this->client->messages->create($to, [
            'from' => $this->fromNumber,
            'body' => $message,
        ]);
    }
}