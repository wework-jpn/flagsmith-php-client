<?php

use Flagsmith\Laravel\FlagsmithContext;
use PHPUnit\Framework\TestCase;

class FlagsmithContextTest extends TestCase
{
    public function testFromArrayUsesUserUuidAsIdentifierAndTrait()
    {
        $context = FlagsmithContext::fromArray([
            'userUuid' => 'user-123',
            'companyUuid' => 'company-123',
            'membershipType' => 'pa',
        ]);

        $this->assertSame('user-123', $context->identifier());
        $this->assertTrue($context->hasIdentity());
        $this->assertSame('user-123', $context->traits()->userUuid);
        $this->assertSame('company-123', $context->traits()->companyUuid);
    }

    public function testFromArrayUsesExplicitTraitsWhenProvided()
    {
        $context = FlagsmithContext::fromArray([
            'identifier' => 'user-123',
            'traits' => [
                'companyUuid' => 'company-123',
            ],
            'transient' => true,
        ]);

        $this->assertSame('user-123', $context->identifier());
        $this->assertSame('company-123', $context->traits()->companyUuid);
        $this->assertFalse(property_exists($context->traits(), 'identifier'));
        $this->assertTrue($context->transient());
    }
}
