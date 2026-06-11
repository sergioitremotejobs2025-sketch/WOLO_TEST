<?php

namespace App\Tests\Service;

use App\Service\VertexAiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class VertexAiClientTest extends TestCase
{
    public function testGenerateContentReturnsResponseArray(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hello from Gemini!']
                        ],
                        'role' => 'model'
                    ]
                ]
            ]
        ]));
        
        $httpClient = new MockHttpClient($mockResponse);
        $client = new VertexAiClient(
            $httpClient, 
            'test-project', 
            'us-central1', 
            'test-token'
        );
        
        $response = $client->generateContent([
            ['role' => 'user', 'parts' => [['text' => 'Hi']]]
        ]);
        
        $this->assertArrayHasKey('candidates', $response);
        $this->assertSame('Hello from Gemini!', $response['candidates'][0]['content']['parts'][0]['text']);
        
        $requestMethod = $mockResponse->getRequestMethod();
        $requestUrl = $mockResponse->getRequestUrl();
        $requestOptions = $mockResponse->getRequestOptions();
        
        $this->assertSame('POST', $requestMethod);
        $this->assertStringContainsString('test-project', $requestUrl);
        $this->assertStringContainsString('us-central1', $requestUrl);
        
        $foundAuth = false;
        foreach ($requestOptions['headers'] as $header) {
            if (stripos($header, 'Authorization: Bearer test-token') === 0) {
                $foundAuth = true;
                break;
            }
        }
        $this->assertTrue($foundAuth, 'Authorization header is missing');
    }

    public function testMetadataServerFallbackWhenDummyToken(): void
    {
        $mockResponses = [
            // First request: Project ID fetch from metadata server
            new MockResponse('metadata-project-id'),
            // Second request: Access Token fetch from metadata server
            new MockResponse(json_encode(['access_token' => 'metadata-retrieved-token'])),
            // Third request: generateContent request to Vertex AI
            new MockResponse(json_encode([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Hello from Gemini fallback!']
                            ],
                            'role' => 'model'
                        ]
                    ]
                ]
            ]))
        ];

        $httpClient = new MockHttpClient($mockResponses);
        $client = new VertexAiClient(
            $httpClient,
            'dummy-project',
            'us-central1',
            'dummy-token'
        );

        $response = $client->generateContent([
            ['role' => 'user', 'parts' => [['text' => 'Hi']]]
        ]);

        $this->assertArrayHasKey('candidates', $response);
        $this->assertSame('Hello from Gemini fallback!', $response['candidates'][0]['content']['parts'][0]['text']);

        // Inspect the 3 requests made
        // Request 1: Project ID metadata
        // Request 2: Access Token metadata
        // Request 3: Vertex AI API
        
        // Wait, MockHttpClient doesn't have a direct getRequests() in older/some versions, 
        // but we can retrieve them by calling $mockResponses elements or checking request attributes.
        // Actually, we can get request properties from the responses directly:
        $req1Url = $mockResponses[0]->getRequestUrl();
        $req2Url = $mockResponses[1]->getRequestUrl();
        $req3Url = $mockResponses[2]->getRequestUrl();

        $this->assertSame('http://metadata.google.internal/computeMetadata/v1/project/project-id', $req1Url);
        $this->assertSame('http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token?scopes=https://www.googleapis.com/auth/cloud-platform', $req2Url);
        
        // Check that request 3 used the retrieved metadata token
        $request3Options = $mockResponses[2]->getRequestOptions();
        $foundAuth = false;
        foreach ($request3Options['headers'] as $header) {
            if (stripos($header, 'Authorization: Bearer metadata-retrieved-token') === 0) {
                $foundAuth = true;
                break;
            }
        }
        $this->assertTrue($foundAuth, 'Authorization header with metadata-retrieved-token is missing');
    }
}

