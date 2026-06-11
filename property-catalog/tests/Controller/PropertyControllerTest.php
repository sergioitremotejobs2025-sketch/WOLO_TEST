<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PropertyControllerTest extends WebTestCase
{
    public function testSearchPropertiesReturnsJsonResponse(): void
    {
        $client = static::createClient();
        
        // Ensure the db is clean or seeded. Since this is an SQLite test env, 
        // it may be empty, but we can verify the 200 OK and valid JSON format.
        $client->request('GET', '/api/properties', [
            'type' => 'rent',
            'city' => 'Barcelona',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertJson($content);
    }
}
