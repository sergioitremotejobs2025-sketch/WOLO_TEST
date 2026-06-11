<?php

namespace App\Tests\Controller;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PropertyWebControllerTest extends WebTestCase
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

        // Seed data
        $propertyA = new Property();
        $propertyA->setTitle('Beautiful Madrid Penthouse')
            ->setPrice(1500)
            ->setType('rent')
            ->setBedrooms(2)
            ->setLocation('Madrid');
        
        $propertyB = new Property();
        $propertyB->setTitle('Barcelona Luxury Apartment')
            ->setPrice(500000)
            ->setType('buy')
            ->setBedrooms(3)
            ->setLocation('Barcelona');

        $this->entityManager->persist($propertyA);
        $this->entityManager->persist($propertyB);
        $this->entityManager->flush();
    }

    public function testBrowsePageReturnsSuccessfulResponseAndContainsUI(): void
    {
        $crawler = $this->client->request('GET', '/properties');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1:contains("Browse Properties")');
        
        // Assert filters exist
        $this->assertSelectorExists('input#filter-city');
        $this->assertSelectorExists('select#filter-type');
        $this->assertSelectorExists('input#filter-price-max');
        
        // Assert grid container exists
        $this->assertSelectorExists('#property-grid');

        // Assert seeded properties are displayed on initial load
        $this->assertSelectorExists('h3:contains("Beautiful Madrid Penthouse")');
        $this->assertSelectorExists('h3:contains("Barcelona Luxury Apartment")');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}

