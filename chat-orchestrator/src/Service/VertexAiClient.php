<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class VertexAiClient
{
    private HttpClientInterface $httpClient;
    private string $projectId;
    private string $region;
    private string $token;
    private string $modelName;

    public function __construct(
        HttpClientInterface $httpClient,
        string $projectId,
        string $region,
        string $token,
        string $modelName = 'gemini-1.5-flash-001'
    ) {
        $this->httpClient = $httpClient;
        $this->projectId = $projectId;
        $this->region = $region;
        $this->token = $token;
        $this->modelName = $modelName;
    }

    public function generateContent(array $messages, array $tools = []): array
    {
        $url = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $this->region,
            $this->projectId,
            $this->region,
            $this->modelName
        );

        $payload = [
            'contents' => $messages,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return $response->toArray();
    }
}
