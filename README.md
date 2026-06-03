# Flagsmith Laravel Client

Fork of the Flagsmith PHP SDK with Laravel package auto-discovery and a GMP-free hashing implementation.

This fork is based on `Flagsmith/flagsmith-php-client` `v5.1.1`. It keeps the upstream SDK code and features, removes the `ext-gmp` dependency, and adds Laravel service provider, facade, and config publishing support.

## Requirements

- PHP `>=8.1`
- `ext-bcmath`
- PSR-17 and PSR-18 implementations, such as Guzzle and PSR-7

This fork does not require `ext-gmp`.

## Installation

This package is not published on Packagist, so add it to your application's `composer.json` as a VCS repository before requiring it:

```bash
composer config repositories.flagsmith-laravel-client vcs https://github.com/wework-jpn/flagsmith-laravel-client.git
composer require wework-japan/flagsmith-laravel-client:dev-main
```

If Composer cannot access the GitHub API because of local SSL certificate issues, use SSH and disable GitHub API metadata lookups:

```bash
composer config repositories.flagsmith-laravel-client '{"type":"vcs","url":"git@github.com:wework-jpn/flagsmith-laravel-client.git","no-api":true}'
composer require wework-japan/flagsmith-laravel-client:dev-main
```

To install a specific tagged release:

```bash
composer require wework-japan/flagsmith-laravel-client:v1.0.0
```

Replace `v1.0.0` with the tag you want to install. The branch or tag must contain a `composer.json` whose `name` is `wework-japan/flagsmith-laravel-client`.

## Updating

If your application requires `dev-main`, update to the latest commit on the `main` branch with:

```bash
composer update wework-japan/flagsmith-laravel-client
```

To move to a different tag, require the new tag explicitly:

```bash
composer require wework-japan/flagsmith-laravel-client:v1.0.1
```

## Laravel Setup

Laravel will auto-discover `Flagsmith\Laravel\FlagsmithServiceProvider` and the `Flagsmith` facade alias.

Publish the config file:

```bash
php artisan vendor:publish --provider="Flagsmith\Laravel\FlagsmithServiceProvider" --tag=flagsmith-config
```

Set your environment key in `.env`:

```dotenv
FLAGSMITH_API_KEY=your-environment-key
FLAGSMITH_HOST=https://edge.api.flagsmith.com/api/v1
FLAGSMITH_ENVIRONMENT_TTL=
FLAGSMITH_ENABLE_ANALYTICS=false
FLAGSMITH_OFFLINE_MODE=false
FLAGSMITH_OFFLINE_HANDLER=
```

When `FLAGSMITH_OFFLINE_MODE=true`, `FLAGSMITH_OFFLINE_HANDLER` must be the class name of a service that implements `Flagsmith\Offline\IOfflineHandler`.

Use the SDK through Laravel's container:

```php
use Flagsmith\Flagsmith;

class FeatureController
{
    public function __invoke(Flagsmith $flagsmith)
    {
        $flags = $flagsmith->getIdentityFlags((string) auth()->id());

        return [
            'new_checkout' => $flags->isFeatureEnabled('new_checkout'),
            'button_colour' => $flags->getFeatureValue('button_colour'),
        ];
    }
}
```

Or use the facade:

```php
use Flagsmith\Laravel\Facades\Flagsmith;

$flags = Flagsmith::getEnvironmentFlags();

if ($flags->isFeatureEnabled('new_checkout')) {
    // ...
}
```

## PHP SDK Usage

You can still use the underlying PHP SDK directly:

```php
use Flagsmith\Flagsmith;

$flagsmith = new Flagsmith(apiKey: 'your-environment-key');
$flags = $flagsmith->getEnvironmentFlags();

if ($flags->isFeatureEnabled('new_checkout')) {
    // ...
}
```

For full SDK documentation, see the Flagsmith server-side client docs: https://docs.flagsmith.com/clients/server-side
