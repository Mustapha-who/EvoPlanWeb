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

    public function generateEventDescription(Event $event): string
    {
        $response = $this->client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openRouterToken,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'https://yourapp.com/',
                'X-Title' => 'EvoPlanEventGenerator',
            ],
            'json' => [
                'model' => 'openai/gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en rédaction de descriptions d\'événements. Ta mission est de créer des descriptions attractives, détaillées et engageantes pour des événements divers.'
                    ],
                    [
                        'role' => 'user',
                        'content' => sprintf(
                            "Crée une description complète pour un événement nommé '%s' qui aura lieu le %s à %s. \n" .
                            "Prix: %s TND\n" .
                            "Capacité: %s personnes\n" .
                            "Statut: %s\n" .
                            "La description doit être engageante, détaillée (entre 150 et 300 mots) et inclure:\n" .
                            "- Une introduction accrocheuse\n" .
                            "- Les points forts de l'événement\n" .
                            "- Ce que les participants vont apprendre/expérimenter\n" .
                            "- Pourquoi cet événement est unique\n" .
                            "- Un appel à l'action motivant",
                            $event->getNom(),
                            $event->getDateDebut() ? $event->getDateDebut()->format('d/m/Y H:i') : '[date non définie]',
                            $event->getLieu() ? $event->getLieu()->value : '[lieu non défini]',
                            $event->getPrix() ?? 0,
                            $event->getCapacite() ?? 0,
                            $event->getStatut() ? $event->getStatut()->value : '[statut non défini]'
                        )
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 600
            ]
        ]);

        $content = json_decode($response->getContent(false), true);

        if (!isset($content['choices'][0]['message']['content'])) {
            throw new \Exception('Erreur lors de la génération de la description: ' . ($content['error']['message'] ?? 'Réponse inattendue'));
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
