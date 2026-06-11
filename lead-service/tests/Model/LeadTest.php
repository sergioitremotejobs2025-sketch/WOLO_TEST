<?php

namespace App\Tests\Model;

use App\Model\Lead;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LeadTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidLead(): void
    {
        $lead = new Lead();
        $lead->name = 'John Doe';
        $lead->email = 'john@example.com';
        $lead->phone = '123456789';
        $lead->propertyId = 42;

        $violations = $this->validator->validate($lead);
        $this->assertCount(0, $violations);
    }

    public function testInvalidEmail(): void
    {
        $lead = new Lead();
        $lead->name = 'John Doe';
        $lead->email = 'not-an-email';
        $lead->propertyId = 42;

        $violations = $this->validator->validate($lead);
        $this->assertCount(1, $violations);
        $this->assertSame('email', $violations[0]->getPropertyPath());
    }

    public function testMissingRequiredFields(): void
    {
        $lead = new Lead(); // everything null

        $violations = $this->validator->validate($lead);
        $this->assertGreaterThan(0, count($violations));
        
        $paths = [];
        foreach ($violations as $v) {
            $paths[] = $v->getPropertyPath();
        }
        
        $this->assertContains('name', $paths);
        $this->assertContains('email', $paths);
        $this->assertContains('propertyId', $paths);
    }
}
