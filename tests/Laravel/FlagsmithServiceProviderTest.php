<?php

use Flagsmith\Cache as FlagsmithCache;
use Flagsmith\Flagsmith as FlagsmithClient;
use Flagsmith\Laravel\Contracts\FlagsmithContextProvider;
use Flagsmith\Laravel\FlagsmithContext;
use Flagsmith\Laravel\FlagsmithManager;
use Flagsmith\Laravel\FlagsmithServiceProvider;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return '/tmp/' . ltrim($path, '/');
    }
}

class FlagsmithServiceProviderTest extends TestCase
{
    public function testItRegistersManagerAliasAndMergesDefaultConfig()
    {
        $app = ProviderTestApplication::newWithConfig([
            'flagsmith' => [
                'enabled' => false,
            ],
        ]);

        (new FlagsmithServiceProvider($app))->register();

        $this->assertFalse($app->make('config')->get('flagsmith')['enabled']);
        $this->assertArrayHasKey('server_side_environment_key', $app->make('config')->get('flagsmith'));
        $this->assertSame(
            $app->make(FlagsmithManager::class),
            $app->make('flagsmith')
        );
        $this->assertFalse($app->make('flagsmith')->isEnabled('anything', default: true));
    }

    public function testItConfiguresRawClientWithServerSideKeyCustomHostAndCache()
    {
        $cache = new ProviderTestCache();
        $app = ProviderTestApplication::newWithConfig([
            'flagsmith' => [
                'server_side_environment_key' => 'ser.test-key',
                'api_key' => 'client-key-that-should-not-be-used',
                'host' => 'https://flagsmith.api.ww-jpn.com/api/v1',
                'environment_ttl' => 60,
                'auto_update_environment' => false,
                'cache' => [
                    'store' => 'redis',
                    'prefix' => 'custom-flagsmith',
                    'ttl' => 90,
                ],
            ],
        ]);
        $app->instance('cache', new stdClass());
        $app->instance(CacheFactory::class, new ProviderTestCacheFactory($cache));

        (new FlagsmithServiceProvider($app))->register();

        $client = $app->make(FlagsmithClient::class);
        $cacheWrapper = $this->privateProperty($client, 'cache');

        $this->assertSame('ser.test-key', $this->privateProperty($client, 'apiKey'));
        $this->assertSame('https://flagsmith.api.ww-jpn.com/api/v1', $this->privateProperty($client, 'host'));
        $this->assertSame(60, $this->privateProperty($client, 'environmentTtl'));
        $this->assertInstanceOf(FlagsmithCache::class, $cacheWrapper);
        $this->assertSame('custom-flagsmith.Environment', $cacheWrapper->getKeyWithPrefix('Environment'));
        $this->assertSame(90, $this->privateProperty($cacheWrapper, 'ttl'));
    }

    public function testItAutoUpdatesEnvironmentFromConfiguredCache()
    {
        $environment = json_decode(file_get_contents(__DIR__ . '/../Data/environment.json'));
        $cache = new ProviderTestCache([
            'flagsmith.Environment' => $environment,
        ]);
        $app = ProviderTestApplication::newWithConfig([
            'flagsmith' => [
                'server_side_environment_key' => 'ser.test-key',
                'host' => 'https://flagsmith.api.ww-jpn.com/api/v1',
                'environment_ttl' => 60,
                'auto_update_environment' => true,
                'cache' => [
                    'store' => 'redis',
                    'prefix' => 'flagsmith',
                    'ttl' => 60,
                ],
            ],
        ]);
        $app->instance('cache', new stdClass());
        $app->instance(CacheFactory::class, new ProviderTestCacheFactory($cache));

        (new FlagsmithServiceProvider($app))->register();

        $client = $app->make(FlagsmithClient::class);

        $this->assertNotNull($client->getLocalEvaluationContext());
        $this->assertSame(['flagsmith.Environment'], $cache->getKeys);
        $this->assertTrue($app->make('flagsmith')->isEnabled('some_feature'));
        $this->assertSame('some-value', $app->make('flagsmith')->getValue('some_feature'));
    }

    public function testItResolvesConfiguredContextProviderThroughManager()
    {
        $environment = json_decode(file_get_contents(__DIR__ . '/../Data/environment.json'));
        $cache = new ProviderTestCache([
            'flagsmith.Environment' => $environment,
        ]);
        $app = ProviderTestApplication::newWithConfig([
            'flagsmith' => [
                'server_side_environment_key' => 'ser.test-key',
                'environment_ttl' => 60,
                'auto_update_environment' => true,
                'context_provider' => ProviderTestContextProvider::class,
                'cache' => [
                    'store' => 'redis',
                    'prefix' => 'flagsmith',
                    'ttl' => 60,
                ],
            ],
        ]);
        $app->instance('cache', new stdClass());
        $app->instance(CacheFactory::class, new ProviderTestCacheFactory($cache));
        $app->instance(ProviderTestContextProvider::class, new ProviderTestContextProvider());

        (new FlagsmithServiceProvider($app))->register();

        $manager = $app->make('flagsmith');
        $context = $manager->context();

        $this->assertSame('provider-user', $context->identifier());
        $this->assertSame('provider-company', $context->traits()->companyUuid);
        $this->assertTrue($manager->isEnabled('some_feature'));
    }

    public function testBootRegistersConfigPublishPath()
    {
        FlagsmithServiceProvider::$publishes = [];
        FlagsmithServiceProvider::$publishGroups = [];

        $app = ProviderTestApplication::newWithConfig();
        $provider = new FlagsmithServiceProvider($app);

        $provider->boot();

        $this->assertArrayHasKey('flagsmith-config', FlagsmithServiceProvider::$publishGroups);
        $this->assertSame(
            '/tmp/flagsmith.php',
            array_values(FlagsmithServiceProvider::$publishGroups['flagsmith-config'])[0]
        );
    }

    private function privateProperty(object $object, string $property)
    {
        $reflection = new ReflectionObject($object);

        while (!$reflection->hasProperty($property)) {
            $reflection = $reflection->getParentClass();
        }

        $propertyReflection = $reflection->getProperty($property);

        return $propertyReflection->getValue($object);
    }
}

class ProviderTestApplication implements ArrayAccess
{
    private array $instances = [];
    private array $singletons = [];
    private array $aliases = [];

    public static function newWithConfig(array $config = []): self
    {
        $app = new self();
        $app->instance('config', new ProviderTestConfig($config));

        return $app;
    }

    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function singleton(string $abstract, Closure $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    public function bound(string $abstract): bool
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        return array_key_exists($abstract, $this->instances)
            || array_key_exists($abstract, $this->singletons);
    }

    public function make(string $abstract)
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        if (array_key_exists($abstract, $this->singletons)) {
            return $this->instances[$abstract] = $this->singletons[$abstract]($this);
        }

        if (class_exists($abstract)) {
            return $this->instances[$abstract] = new $abstract();
        }

        throw new RuntimeException("Nothing bound for {$abstract}.");
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->bound($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->make($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->instance($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->instances[$offset], $this->singletons[$offset], $this->aliases[$offset]);
    }
}

class ProviderTestConfig
{
    public function __construct(private array $items = [])
    {
    }

    public function get(string $key, $default = null)
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->items[$key] = $value;
    }
}

class ProviderTestCacheFactory implements CacheFactory
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function store($name = null)
    {
        return $this->cache;
    }
}

class ProviderTestCache implements CacheInterface
{
    public array $getKeys = [];

    public function __construct(private array $items = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->getKeys[] = $key;

        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->items[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}

class ProviderTestContextProvider implements FlagsmithContextProvider
{
    public function getContext()
    {
        return FlagsmithContext::make('provider-user', [
            'companyUuid' => 'provider-company',
        ]);
    }
}
