<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ToolDispatcher
{
    private HttpClientInterface $catalogClient;
    private string $catalogUrl;

    public function __construct(HttpClientInterface $catalogClient, string $catalogUrl = 'http://localhost/api/properties')
    {
        $this->catalogClient = $catalogClient;
        $this->catalogUrl = $catalogUrl;
    }

    public function dispatch(string $functionName, array $args): array
    {
        if ($functionName === 'search_properties') {
            return $this->searchProperties($args);
        }

        throw new \InvalidArgumentException("Unknown function: $functionName");
    }

    private function searchProperties(array $args): array
    {
        $response = $this->catalogClient->request('GET', $this->catalogUrl, [
            'query' => $args
        ]);
        
        return $response->toArray();
    }
}
