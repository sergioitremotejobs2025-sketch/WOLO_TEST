<?php

namespace App\Tests\Entity;

use App\Entity\Property;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class PropertyTest extends TestCase
{
    public function testValidPropertyCanBeCreated(): void
    {
        $property = new Property();
        $property->setTitle('Cozy Apartment');
        $property->setPrice(1200);
        $property->setType('rent');
        $property->setBedrooms(2);
        $property->setLocation('Barcelona');
        $property->setEmbeddings(array_fill(0, 768, 0.1)); // 768 dim vector

        $this->assertSame('Cozy Apartment', $property->getTitle());
        $this->assertSame(1200.0, $property->getPrice());
        $this->assertSame('rent', $property->getType());
        $this->assertSame(2, $property->getBedrooms());
        $this->assertSame('Barcelona', $property->getLocation());
        $this->assertCount(768, $property->getEmbeddings());
    }

    public function testPropertyValidationFailsOnInvalidData(): void
    {
        $property = new Property();
        $property->setTitle(''); // Invalid: Blank
        $property->setPrice(-500); // Invalid: Negative
        $property->setType('invalid_type'); // Invalid: Choice

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($property);

        // We expect violations on title, price, and type
        $this->assertGreaterThan(0, count($violations));
    }
}
