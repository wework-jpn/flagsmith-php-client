<?php

declare(strict_types=1);

namespace Flagsmith\Laravel;

use Flagsmith\Flagsmith;
use Flagsmith\Laravel\Contracts\FlagsmithContextProvider;
use Flagsmith\Offline\IOfflineHandler;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

class FlagsmithServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/flagsmith.php', 'flagsmith');

        $this->app->singleton(Flagsmith::class, static function ($app): Flagsmith {
            $config = $app['config']->get('flagsmith', []);
            $cacheConfig = $config['cache'] ?? [];
            $offlineHandler = null;

            if (!empty($config['offline_handler'])) {
                $offlineHandler = is_string($config['offline_handler'])
                    ? $app->make($config['offline_handler'])
                    : $config['offline_handler'];
            }

            if ($offlineHandler !== null && !$offlineHandler instanceof IOfflineHandler) {
                throw new InvalidArgumentException('The configured Flagsmith offline handler must implement ' . IOfflineHandler::class . '.');
            }

            $client = new Flagsmith(
                apiKey: $config['server_side_environment_key'] ?? $config['api_key'] ?? null,
                host: $config['host'] ?? null,
                environmentTtl: $config['environment_ttl'] ?? null,
                enableAnalytics: $config['enable_analytics'] ?? false,
                offlineMode: $config['offline_mode'] ?? false,
                offlineHandler: $offlineHandler,
            );

            if (!empty($cacheConfig['store']) && $app->bound('cache')) {
                $cache = $app->make(CacheFactory::class)->store($cacheConfig['store']);

                if (!$cache instanceof CacheInterface) {
                    throw new InvalidArgumentException('The configured Flagsmith cache store must implement ' . CacheInterface::class . '.');
                }

                $client
                    ->withCache($cache)
                    ->withCachePrefix($cacheConfig['prefix'] ?? 'flagsmith')
                    ->withTimeToLive($cacheConfig['ttl'] ?? null);
            }

            if (!empty($config['auto_update_environment']) && !empty($config['environment_ttl'])) {
                $client->updateEnvironment();
            }

            return $client;
        });

        $this->app->singleton(FlagsmithManager::class, static function ($app): FlagsmithManager {
            $config = $app['config']->get('flagsmith', []);
            $contextProvider = null;

            if (!empty($config['context_provider'])) {
                $contextProvider = $app->make($config['context_provider']);
            }

            if ($contextProvider !== null && !$contextProvider instanceof FlagsmithContextProvider) {
                throw new InvalidArgumentException('The configured Flagsmith context provider must implement ' . FlagsmithContextProvider::class . '.');
            }

            return new FlagsmithManager(
                clientResolver: static fn () => $app->make(Flagsmith::class),
                config: $config,
                contextProvider: $contextProvider,
            );
        });

        $this->app->alias(FlagsmithManager::class, 'flagsmith');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/flagsmith.php' => config_path('flagsmith.php'),
        ], 'flagsmith-config');
    }
}
