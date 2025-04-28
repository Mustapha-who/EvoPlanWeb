<?php

namespace App\Service\UserModule;
use HTTP_Request2;
class InfoBipSMS
{
    private string $infoBipapiUrl;
    private string $infoBipapiKey;
    private string $infoBipsender;

    public function __construct(string $infoBipapiUrl, string $infoBipapiKey, string $infoBipsender)
    {
        $this->apiUrl = $infoBipapiUrl;
        $this->apiKey = $infoBipapiKey;
        $this->sender = $infoBipsender;
    }

    public function sendSms(string $to, string $message): bool
    {
        $request = new HTTP_Request2();
        $request->setUrl($this->apiUrl);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setConfig(['follow_redirects' => true]);
        $request->setHeader([
            'Authorization' => 'App ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $body = [
            'messages' => [
                [
                    'destinations' => [['to' => $to]],
                    'from' => $this->sender,
                    'text' => $message,
                ],
            ],
        ];

        $request->setBody(json_encode($body));

        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                return true;
            } else {
                // Log the error or handle it as needed
                return false;
            }
        } catch (\HTTP_Request2_Exception $e) {
            // Log the exception or handle it as needed
            return false;
        }
    }
}