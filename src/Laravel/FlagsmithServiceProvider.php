<?php

declare(strict_types=1);

namespace Flagsmith\Laravel;

use Flagsmith\Flagsmith;
use Illuminate\Support\ServiceProvider;

class FlagsmithServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/flagsmith.php', 'flagsmith');

        $this->app->singleton(Flagsmith::class, static function ($app): Flagsmith {
            $config = $app['config']->get('flagsmith', []);

            return new Flagsmith(
                apiKey: $config['api_key'] ?? null,
                host: $config['host'] ?? null,
                environmentTtl: $config['environment_ttl'] ?? null,
                enableAnalytics: $config['enable_analytics'] ?? false,
                offlineMode: $config['offline_mode'] ?? false,
            );
        });

        $this->app->alias(Flagsmith::class, 'flagsmith');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/flagsmith.php' => config_path('flagsmith.php'),
        ], 'flagsmith-config');
    }
}
