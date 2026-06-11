<?php

namespace App\Tests\Repository;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class PropertyRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PropertyRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->repository = $this->entityManager->getRepository(Property::class);

        // Build schema
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metaData);
        $schemaTool->createSchema($metaData);

        // Seed data
        $propertyA = new Property();
        $propertyA->setTitle('Flat A')->setPrice(1000)->setType('rent')->setBedrooms(2)->setLocation('Madrid');
        
        $propertyB = new Property();
        $propertyB->setTitle('Flat B')->setPrice(2000)->setType('buy')->setBedrooms(3)->setLocation('Madrid');
        
        $propertyC = new Property();
        $propertyC->setTitle('Flat C')->setPrice(1200)->setType('rent')->setBedrooms(2)->setLocation('Barcelona');

        $this->entityManager->persist($propertyA);
        $this->entityManager->persist($propertyB);
        $this->entityManager->persist($propertyC);
        $this->entityManager->flush();
    }

    public function testFiltersPropertiesByLocationAndType(): void
    {
        $results = $this->repository->searchByCriteria(['type' => 'rent', 'location' => 'Madrid']);
        
        $this->assertCount(1, $results);
        $this->assertSame('Flat A', $results[0]->getTitle());
    }

    public function testFiltersPropertiesByMaxPrice(): void
    {
        $results = $this->repository->searchByCriteria(['type' => 'rent', 'max_price' => 1500, 'location' => 'Madrid']);
        
        $this->assertCount(1, $results);
        $this->assertSame('Flat A', $results[0]->getTitle());
    }

    public function testSemanticSearch(): void
    {
        if ($this->entityManager->getConnection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
            $this->markTestSkipped('Vector search using pgvector is not supported in the SQLite test environment.');
        }

        // Normally we would generate a 768-dimensional array and query it.
        $results = $this->repository->findBySemanticSearch(array_fill(0, 768, 0.5));
        $this->assertIsArray($results);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
