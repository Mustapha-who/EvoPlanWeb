<?php



namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoService
{
    private HttpClientInterface $client;
    private string $apiKey;

    // Liste des villes tunisiennes pour les prévisions
    private array $villes = ['Tunis', 'Sfax', 'Sousse', 'Gabès', 'Bizerte', 'Kairouan'];

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getAllPrevisions(): array
    {
        $previsions = [];

        foreach ($this->villes as $ville) {
            try {
                $url = 'https://api.openweathermap.org/data/2.5/weather';
                $response = $this->client->request('GET', $url, [
                    'query' => [
                        'q' => $ville . ',TN',
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => 'fr',
                    ],
                ]);

                $data = $response->toArray();

                $previsions[] = [
                    'ville' => $ville,
                    'temperature' => round($data['main']['temp']),
                    'description' => ucfirst($data['weather'][0]['description']),
                ];
            } catch (\Exception $e) {
                $previsions[] = [
                    'ville' => $ville,
                    'temperature' => 'N/A',
                    'description' => 'Erreur lors du chargement: ' . $e->getMessage(),
                ];
            }
        }

        return $previsions;
    }
}
