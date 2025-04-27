<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class OpenRouterApiService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const API_KEY = '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';

    public function __construct(private HttpClientInterface $httpClient) {}

    public function generateWorkshopData(string $prompt): array
    {
        try {
            // Generate title with specific word count instruction
            $titleResponse = $this->makeApiRequest([
                'system' => 'You are a workshop title generator. Generate ONLY a concise, professional title (between 3-8 words) for a health-related workshop. DO NOT include any explanations or additional text. ONLY return the title.',
                'user' => "Generate a brief workshop title (3-8 words maximum) for: $prompt"
            ]);

            if (!$titleResponse) {
                throw new \RuntimeException('Failed to generate workshop title');
            }

            // Clean and validate title length
            $title = $this->cleanAndFormatTitle($titleResponse);

            // Generate description based on title
            $description = $this->makeApiRequest([
                'system' => 'Generate a brief, professional workshop description in 2-3 sentences.',
                'user' => "Write a concise description for a workshop titled: $title"
            ]);

            return [
                'title' => $title,
                'description' => $description ?? 'No description available.'
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Workshop generation failed: ' . $e->getMessage());
        }
    }

    private function cleanAndFormatTitle(string $title): string
    {
        // Remove any potential explanation text or quotes
        $title = preg_replace('/^(title:|"|\')|\s*("|\'|\.)$/i', '', trim($title));
        
        // Split into words and limit to 3-8 words
        $words = explode(' ', $title);
        if (count($words) < 3) {
            throw new \RuntimeException('Title must be at least 3 words');
        }
        
        $words = array_slice($words, 0, min(8, count($words)));
        
        // Format each word (capitalize first letter)
        return implode(' ', array_map('ucfirst', array_map('strtolower', $words)));
    }

    private function makeApiRequest(array $messages): ?string
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::API_KEY,
                    'HTTP-Referer' => 'http://localhost',
                    'X-Title' => 'Workshop Generator',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek/deepseek-chat:free',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $messages['system']
                        ],
                        [
                            'role' => 'user',
                            'content' => $messages['user']
                        ]
                    ]
                ]
            ]);

            $data = json_decode($response->getContent(), true);
            return trim($data['choices'][0]['message']['content'] ?? null);
        } catch (\Exception $e) {
            return null;
        }
    }
}
