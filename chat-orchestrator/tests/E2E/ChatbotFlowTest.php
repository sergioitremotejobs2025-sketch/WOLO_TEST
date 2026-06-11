<?php

namespace App\Tests\E2E;

use App\Service\VertexAiClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotFlowTest extends WebTestCase
{
    public function testCompleteChatbotFlowTriggeringToolAndLead(): void
    {
        $client = static::createClient();

        // Mock Vertex AI
        $mockVertexClient = $this->createMock(VertexAiClient::class);
        $mockVertexClient->expects($this->exactly(2))
            ->method('generateContent')
            ->willReturnOnConsecutiveCalls(
                [
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'functionCall' => [
                                            'name' => 'search_properties',
                                            'args' => ['city' => 'Madrid', 'type' => 'rent']
                                        ]
                                    ]
                                ],
                                'role' => 'model'
                            ]
                        ]
                    ]
                ],
                [
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'I found some properties in Madrid for rent. Would you like to schedule a visit?']
                                ],
                                'role' => 'model'
                            ]
                        ]
                    ]
                ]
            );

        static::getContainer()->set(VertexAiClient::class, $mockVertexClient);

        // Mock HTTP Client (used by ToolDispatcher to reach property-catalog)
        $mockResponse = new MockResponse(json_encode([
            ['id' => 1, 'title' => 'Flat in Madrid', 'price' => 1200]
        ]));
        $mockHttpClient = new MockHttpClient([$mockResponse]);
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $client->request(
            'POST',
            '/api/chat',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'sessionId' => 'e2e-session',
                'message' => 'I am looking for a flat to rent in Madrid'
            ])
        );

        $this->assertResponseIsSuccessful();
        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('response', $responseContent);
        $this->assertSame(
            'I found some properties in Madrid for rent. Would you like to schedule a visit?',
            $responseContent['response']
        );
    }
}

