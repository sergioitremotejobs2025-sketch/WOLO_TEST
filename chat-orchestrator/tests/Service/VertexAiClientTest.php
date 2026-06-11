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
}
