<?php

namespace App\Service;

use App\Entity\Event;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIContentGenerator
{
    private HttpClientInterface $client;
    private string $openRouterToken;

    public function __construct(HttpClientInterface $client, string $openRouterToken)
    {
        $this->client = $client;
        $this->openRouterToken = $openRouterToken;
    }

    public function generateCaption(Event $event, string $platform = 'facebook', ?string $forceModel = null): string
    {
        // Choix automatique du modèle
        $model = $forceModel ?? $this->chooseModel($event);

        $response = $this->client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openRouterToken,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'https://yourapp.com/', // remplace avec ton site ou localhost
                'X-Title' => 'EvoPlanCaptionGenerator',   // titre de ton app sur OpenRouter
            ],
            'json' => [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => <<<PROMPT
Rédige une courte légende attrayante pour promouvoir un événement nommé "{$event->getNom()}" qui aura lieu le {$event->getDateDebut()->format('d/m/Y H:i')} à {$event->getLieu()->value}. 
Utilise un ton motivant et engageant, adapté à un post sur {$platform}. Fais en sorte d'inspirer les gens à y participer !
PROMPT,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 150,
            ],
        ]);

        $content = json_decode($response->getContent(false), true);

        if (!isset($content['choices'][0]['message']['content'])) {
            throw new \Exception('Erreur OpenRouter: Réponse inattendue.');
        }

        return trim($content['choices'][0]['message']['content']);
    }

    private function chooseModel(Event $event): string
    {
        $nom = strtolower($event->getNom());

        if (str_contains($nom, 'fête') || str_contains($nom, 'festival') || str_contains($nom, 'soirée') || str_contains($nom, 'party')) {
            return 'mistralai/mixtral-8x7b-instruct'; // très créatif
        } elseif (str_contains($nom, 'conférence') || str_contains($nom, 'séminaire') || str_contains($nom, 'forum')) {
            return 'google/gemma-7b-it'; // plus sérieux
        } else {
            return 'openai/gpt-3.5-turbo'; // polyvalent
        }
    }
}
