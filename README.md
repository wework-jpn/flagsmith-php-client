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

## Development Checks

Install development dependencies, then install the repository Git hooks:

```bash
composer install
composer hooks:install
```

Available quality commands:

```bash
composer format
composer lint
composer analyse
composer test
composer quality
```

The pre-commit hook runs `composer lint`, `composer analyse`, and `composer test`. A commit is blocked when any of them fails.

The commit-msg hook enforces this conventional commit header format and requires a non-empty body:

```text
<type>(<scope>): <subject> [JDTD-123]
```

Allowed types are `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `build`, `ci`, `chore`, `perf`, and `revert`.

## Laravel Setup

Laravel will auto-discover `Flagsmith\Laravel\FlagsmithServiceProvider` and the `Flagsmith` facade alias.

Publish the config file:

```bash
php artisan vendor:publish --provider="Flagsmith\Laravel\FlagsmithServiceProvider" --tag=flagsmith-config
```

For local evaluation, use a server-side environment key. Client-side environment keys can call the API, but they cannot download the environment document required by local evaluation.

```dotenv
FLAGSMITH_ENABLED=true
FLAGSMITH_HOST=https://flagsmith.api.ww-jpn.com/api/v1
FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY=ser.your-server-side-environment-key
FLAGSMITH_ENVIRONMENT_TTL=60
FLAGSMITH_CACHE_STORE=redis
FLAGSMITH_CACHE_PREFIX=flagsmith
FLAGSMITH_CACHE_TTL=60
FLAGSMITH_ENABLE_ANALYTICS=false
FLAGSMITH_AUTO_UPDATE_ENVIRONMENT=true
FLAGSMITH_CONTEXT_PROVIDER=App\Providers\FlagsmithContextProvider
FLAGSMITH_OFFLINE_MODE=false
FLAGSMITH_OFFLINE_HANDLER=
```

`FLAGSMITH_API_KEY` is still supported as a fallback, but `FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY` is preferred when `FLAGSMITH_ENVIRONMENT_TTL` is set.

When `FLAGSMITH_AUTO_UPDATE_ENVIRONMENT=true`, the SDK loads the environment document into the current request's client instance. With `FLAGSMITH_CACHE_STORE=redis`, that environment document is cached according to `FLAGSMITH_ENVIRONMENT_TTL`, so requests reuse Redis until the TTL expires.

When `FLAGSMITH_AUTO_UPDATE_ENVIRONMENT=false`, calls use the Flagsmith API unless your application calls `Flagsmith::updateEnvironment()` before evaluating flags in the same request.

When `FLAGSMITH_OFFLINE_MODE=true`, `FLAGSMITH_OFFLINE_HANDLER` must be the class name of a service that implements `Flagsmith\Offline\IOfflineHandler`.

Use the Laravel facade for Unleash-style checks:

```php
use Flagsmith\Laravel\Facades\Flagsmith;

class FeatureController
{
    public function __invoke()
    {
        return [
            'new_checkout' => Flagsmith::isEnabled('new_checkout'),
            'button_colour' => Flagsmith::getValue('button_colour', default: 'blue'),
        ];
    }
}
```

Pass context explicitly when needed:

```php
use Flagsmith\Laravel\Facades\Flagsmith;

Flagsmith::isEnabled('allow-non-pa-users', [
    'userUuid' => $user->uuid,
    'companyUuid' => $user->company_uuid,
    'membershipType' => $user->active_membership_type,
    'isAdmin' => (string) $user->is_admin,
    'locale' => $user->language_suffix ?? config('app.locale'),
    'seatId' => (string) $user->active_seat_id,
    'seatKind' => $user->activeSeat?->kind,
    'isPrivateAccess' => (string) $user->company?->is_pa,
]);
```

Or configure a context provider:

```php
namespace App\Providers;

use Flagsmith\Laravel\Contracts\FlagsmithContextProvider as ContextProvider;
use Flagsmith\Laravel\FlagsmithContext;

class FlagsmithContextProvider implements ContextProvider
{
    public function getContext(): FlagsmithContext
    {
        $user = request()->user();

        return FlagsmithContext::make(
            identifier: $user?->uuid,
            traits: [
                'companyUuid' => $user?->company_uuid,
                'membershipType' => $user?->active_membership_type,
                'isAdmin' => (string) $user?->is_admin,
                'locale' => $user->language_suffix ?? config('app.locale'),
                'seatId' => (string) $user?->active_seat_id,
                'userUuid' => $user?->uuid,
                'seatKind' => $user?->activeSeat?->kind,
                'isPrivateAccess' => (string) $user?->company?->is_pa,
            ],
        );
    }
}
```

You can still inject the raw upstream SDK from Laravel's container:

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

If you are replacing Unleash, see [UPGRADE-FROM-UNLEASH.md](UPGRADE-FROM-UNLEASH.md).
