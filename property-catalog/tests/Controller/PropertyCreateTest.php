<?php

namespace App\Tests\Controller;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PropertyCreateTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = self::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        // Build schema in test sqlite db
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metaData);
        $schemaTool->createSchema($metaData);
    }

    public function testCreatePropertySuccess(): void
    {
        $this->client->request('POST', '/api/properties', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'New Cozy Flat',
            'price' => 1250.0,
            'type' => 'rent',
            'bedrooms' => 2,
            'location' => 'Seville'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('id', $data);
        $this->assertSame('New Cozy Flat', $data['title']);
        $this->assertSame(1250.0, (float)$data['price']);
        $this->assertSame('rent', $data['type']);
        $this->assertSame(2, $data['bedrooms']);
        $this->assertSame('Seville', $data['location']);

        // Verify database persistence
        $repo = $this->entityManager->getRepository(Property::class);
        $property = $repo->find($data['id']);
        $this->assertNotNull($property);
        $this->assertSame('New Cozy Flat', $property->getTitle());
    }

    public function testCreatePropertyValidationError(): void
    {
        $this->client->request('POST', '/api/properties', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'ab', // too short (min 3)
            'price' => -100.0, // negative price (must be positive)
            'type' => 'invalid_type', // choice error
            'bedrooms' => -1, // must be positive or zero
            'location' => '' // blank
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('title', $data['errors']);
        $this->assertArrayHasKey('price', $data['errors']);
        $this->assertArrayHasKey('type', $data['errors']);
        $this->assertArrayHasKey('bedrooms', $data['errors']);
        $this->assertArrayHasKey('location', $data['errors']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
