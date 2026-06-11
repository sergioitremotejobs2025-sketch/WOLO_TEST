<?php

namespace App\Tests\Controller;

use App\Service\VertexAiClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChatControllerTest extends WebTestCase
{
    public function testChatEndpointRequiresMessage(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['sessionId' => 'test-session']));

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('Message is required', $client->getResponse()->getContent());
    }

    public function testChatEndpointSuccessWithMock(): void
    {
        $client = static::createClient();

        // Create a mock of VertexAiClient
        $mockVertexClient = $this->createMock(VertexAiClient::class);
        $mockVertexClient->method('generateContent')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hello, I am WOLO Assistant.']
                        ],
                        'role' => 'model'
                    ]
                ]
            ]
        ]);

        // Inject the mock into the container
        static::getContainer()->set(VertexAiClient::class, $mockVertexClient);

        $client->request(
            'POST',
            '/api/chat',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['sessionId' => 'test-123', 'message' => 'Hi'])
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Hello, I am WOLO Assistant.', $client->getResponse()->getContent());
    }
}
