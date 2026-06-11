<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LeadControllerTest extends WebTestCase
{
    public function testCaptureValidLead(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/leads',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'phone' => '123456789',
                'propertyId' => 101
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', '*');
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('success', $responseContent['status']);
    }

    public function testCaptureInvalidLead(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/leads',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'J', // too short
                'email' => 'invalid-email',
                // propertyId is omitted to trigger NotBlank validation error
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', '*');
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseContent);
        $this->assertArrayHasKey('name', $responseContent['errors']);
        $this->assertArrayHasKey('email', $responseContent['errors']);
        $this->assertArrayHasKey('propertyId', $responseContent['errors']);
    }
}
