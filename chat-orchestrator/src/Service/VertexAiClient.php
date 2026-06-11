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
        string $modelName = 'gemini-2.5-flash'
    ) {
        $this->httpClient = $httpClient;
        $this->projectId = $projectId;
        $this->region = $region;
        $this->token = $token;
        $this->modelName = $modelName;
    }

    public function generateContent(array $messages, array $tools = []): array
    {
        $projectId = $this->getProjectId();
        $accessToken = $this->getAccessToken();

        $url = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $this->region,
            $projectId,
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
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return $response->toArray();
    }

    private function getAccessToken(): string
    {
        if ($this->token !== 'dummy-token' && !empty($this->token)) {
            return $this->token;
        }

        try {
            $response = $this->httpClient->request('GET', 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token?scopes=https://www.googleapis.com/auth/cloud-platform', [
                'headers' => [
                    'Metadata-Flavor' => 'Google'
                ],
                'timeout' => 2.0
            ]);
            $data = $response->toArray();
            return $data['access_token'] ?? '';
        } catch (\Exception $e) {
            error_log('WOLO VertexAiClient Token Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return $this->token;
        }
    }

    private function getProjectId(): string
    {
        if ($this->projectId !== 'dummy-project' && !empty($this->projectId)) {
            return $this->projectId;
        }

        try {
            $response = $this->httpClient->request('GET', 'http://metadata.google.internal/computeMetadata/v1/project/project-id', [
                'headers' => [
                    'Metadata-Flavor' => 'Google'
                ],
                'timeout' => 2.0
            ]);
            return trim($response->getContent());
        } catch (\Exception $e) {
            error_log('WOLO VertexAiClient Project ID Error: ' . $e->getMessage());
            return $this->projectId;
        }
    }
}
