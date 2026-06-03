<?php

use Flagsmith\Flagsmith as FlagsmithClient;
use Flagsmith\Laravel\Contracts\FlagsmithContextProvider;
use Flagsmith\Laravel\FlagsmithContext;
use Flagsmith\Laravel\FlagsmithManager;
use Flagsmith\Models\Flag;
use Flagsmith\Models\Flags;
use Flagsmith\Utils\Collections\FlagModelsList;
use PHPUnit\Framework\TestCase;

class FlagsmithManagerTest extends TestCase
{
    public function testDisabledManagerReturnsDefaultsWithoutResolvingClient()
    {
        $manager = new FlagsmithManager(
            clientResolver: fn () => throw new RuntimeException('Client should not be resolved.'),
            config: ['enabled' => false],
        );

        $this->assertFalse($manager->isEnabled('anything', default: true));
        $this->assertSame('fallback', $manager->getValue('anything', default: 'fallback'));
    }

    public function testItEvaluatesIdentityFlagsFromExplicitContext()
    {
        $client = new FakeFlagsmithClient();
        $manager = new FlagsmithManager(
            clientResolver: fn () => $client,
            config: ['enabled' => true],
        );

        $this->assertTrue($manager->isEnabled('allow-non-pa-users', [
            'userUuid' => 'user-123',
            'companyUuid' => 'company-123',
        ]));

        $this->assertSame('user-123', $client->lastIdentifier);
        $this->assertSame('company-123', $client->lastTraits->companyUuid);
    }

    public function testItUsesConfiguredContextProvider()
    {
        $client = new FakeFlagsmithClient();
        $manager = new FlagsmithManager(
            clientResolver: fn () => $client,
            config: ['enabled' => true],
            contextProvider: new FakeFlagsmithContextProvider(),
        );

        $this->assertSame('green', $manager->getValue('button-colour'));
        $this->assertSame('provider-user', $client->lastIdentifier);
        $this->assertSame('provider-company', $client->lastTraits->companyUuid);
    }

    public function testMissingFlagReturnsDefault()
    {
        $manager = new FlagsmithManager(
            clientResolver: fn () => new FakeFlagsmithClient(),
            config: ['enabled' => true],
        );

        $this->assertFalse($manager->isEnabled('missing'));
        $this->assertSame('fallback', $manager->getValue('missing', default: 'fallback'));
    }
}

class FakeFlagsmithClient extends FlagsmithClient
{
    public ?string $lastIdentifier = null;
    public ?object $lastTraits = null;

    public function __construct()
    {
    }

    public function getIdentityFlags(string $identifier, ?object $traits = null, ?bool $transient = false): Flags
    {
        $this->lastIdentifier = $identifier;
        $this->lastTraits = $traits;

        return $this->fakeFlags();
    }

    public function getEnvironmentFlags(): Flags
    {
        return $this->fakeFlags();
    }

    private function fakeFlags(): Flags
    {
        $flags = [
            'allow-non-pa-users' => (new Flag())
                ->withFeatureId(1)
                ->withFeatureName('allow-non-pa-users')
                ->withEnabled(true)
                ->withValue(null),
            'button-colour' => (new Flag())
                ->withFeatureId(2)
                ->withFeatureName('button-colour')
                ->withEnabled(true)
                ->withValue('green'),
        ];

        return (new Flags())->withFlags(new FlagModelsList($flags));
    }
}

class FakeFlagsmithContextProvider implements FlagsmithContextProvider
{
    public function getContext()
    {
        return FlagsmithContext::make('provider-user', [
            'companyUuid' => 'provider-company',
        ]);
    }
}
