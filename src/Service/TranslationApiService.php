<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationApiService
{
    private const API_URL = 'https://api.mymemory.translated.net/get';

    public function __construct(private HttpClientInterface $httpClient) {}

    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'q' => $text,
                    'langpair' => $sourceLang . '|' . $targetLang,
                ],
                'headers' => [
                    'Accept-Charset' => 'UTF-8',
                ]
            ]);

            $data = $response->toArray();
            return $data['responseData']['translatedText'] ?? 'Translation failed';
        } catch (\Exception $e) {
            return 'Translation error: ' . $e->getMessage();
        }
    }
}
