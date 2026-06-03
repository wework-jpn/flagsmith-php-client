# Upgrade From Unleash

This guide maps a Laravel Unleash setup to `wework-japan/flagsmith-laravel-client`.

## Key Choice

Use `FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY` when you set `FLAGSMITH_ENVIRONMENT_TTL`.

Flagsmith has two common environment key modes:

- Client-side environment key: can evaluate through the Flagsmith API.
- Server-side environment key, prefixed with `ser.`: required for local evaluation because it can download the environment document.

For this configuration, use the server-side key:

```dotenv
FLAGSMITH_HOST=https://flagsmith.api.ww-jpn.com/api/v1
FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY=ser.your-server-side-environment-key
FLAGSMITH_ENVIRONMENT_TTL=60
FLAGSMITH_CACHE_STORE=redis
FLAGSMITH_CACHE_PREFIX=flagsmith
FLAGSMITH_CACHE_TTL=60
FLAGSMITH_ENABLE_ANALYTICS=false
FLAGSMITH_AUTO_UPDATE_ENVIRONMENT=true
```

`FLAGSMITH_API_KEY` remains supported as a fallback, but prefer `FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY` for local evaluation.

## Auto Update

For PHP/Laravel request lifecycles, set `FLAGSMITH_AUTO_UPDATE_ENVIRONMENT=true` when using local evaluation.

The SDK client is created per Laravel process/request. The environment document must be loaded into that client before local evaluation can happen. With `FLAGSMITH_CACHE_STORE=redis` and `FLAGSMITH_ENVIRONMENT_TTL=60`, the package reads the environment document from Redis until the TTL expires, so this does not mean a remote Flagsmith request on every web request.

Use `FLAGSMITH_AUTO_UPDATE_ENVIRONMENT=false` only if you intentionally want API-backed evaluations, or if you call `Flagsmith::updateEnvironment()` yourself before evaluating flags in the same request.

## Facade

Old Unleash usage:

```php
use App\Support\Facades\Unleash;

if (! Unleash::isEnabled('allow-non-pa-users') && $user->company->is_pa === false) {
    // ...
}
```

New Flagsmith usage:

```php
use Flagsmith\Laravel\Facades\Flagsmith;

if (! Flagsmith::isEnabled('allow-non-pa-users') && $user->company->is_pa === false) {
    // ...
}
```

`Flagsmith::isEnabled($featureName, $context = null, $default = false)` mirrors your Unleash facade behavior:

- If `flagsmith.enabled` is false, it returns `false`.
- If the flag is missing or Flagsmith throws, it returns `$default`.
- If `$context` is omitted, the configured context provider is used.

For remote config values:

```php
$value = Flagsmith::getValue('button-colour', default: 'blue');
```

## Service Provider

You no longer need to build the SDK manually in your app service provider. The package auto-discovers its Laravel provider and binds:

- `Flagsmith\Laravel\FlagsmithManager` for facade-style app usage.
- `Flagsmith\Flagsmith` for the raw upstream PHP SDK.
- `flagsmith` as the facade accessor.

Publish the config:

```bash
php artisan vendor:publish --provider="Flagsmith\Laravel\FlagsmithServiceProvider" --tag=flagsmith-config
```

Then configure `.env`:

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
```

## Context Provider

Old Unleash context providers return an `UnleashContext`. Flagsmith evaluates identity flags with:

- An identity identifier, usually your user UUID.
- Identity traits, which replace Unleash `customContext`.

New provider:

```php
<?php

namespace App\Providers;

use App\Enums\WeWorkAdminStatus;
use App\Enums\WeWorkAdminTypes;
use App\Models\User;
use Flagsmith\Laravel\Contracts\FlagsmithContextProvider as ContextProvider;
use Flagsmith\Laravel\FlagsmithContext;

class FlagsmithContextProvider implements ContextProvider
{
    public function getContext(): FlagsmithContext
    {
        /** @var User|null $user */
        $user = request()->user();

        return self::buildContext(
            companyUuid: $user?->company_uuid,
            membershipType: $user?->active_membership_type,
            isAdmin: $user?->is_admin,
            locale: $user->language_suffix ?? config('app.locale'),
            seatId: $user?->active_seat_id,
            userUuid: $user?->uuid,
            seatKind: $user?->activeSeat?->kind,
            isPrivateAccess: $user?->company?->is_pa,
        );
    }

    public static function buildContext(
        ?string $companyUuid = null,
        ?string $membershipType = null,
        ?bool $isAdmin = null,
        ?WeWorkAdminStatus $wwAdminStatus = null,
        ?WeWorkAdminTypes $wwAdminRole = null,
        ?string $locale = null,
        ?int $seatId = null,
        ?string $userUuid = null,
        ?string $seatKind = null,
        ?bool $isPrivateAccess = null,
    ): FlagsmithContext {
        return FlagsmithContext::make(
            identifier: $userUuid,
            traits: [
                'companyUuid' => $companyUuid,
                'membershipType' => $membershipType,
                'isAdmin' => ! is_null($isAdmin) ? (string) $isAdmin : null,
                'wwAdminStatus' => ! is_null($wwAdminStatus) ? (string) $wwAdminStatus->value : null,
                'wwAdminRole' => ! is_null($wwAdminRole) ? (string) $wwAdminRole->value : null,
                'locale' => $locale,
                'seatId' => ! is_null($seatId) ? (string) $seatId : null,
                'userUuid' => $userUuid,
                'seatKind' => $seatKind,
                'isPrivateAccess' => ! is_null($isPrivateAccess) ? (string) $isPrivateAccess : null,
            ],
        );
    }
}
```

Set the provider in `.env`:

```dotenv
FLAGSMITH_CONTEXT_PROVIDER=App\Providers\FlagsmithContextProvider
```

## Explicit Context

If you do not want a global provider for a specific check, pass context directly:

```php
use Flagsmith\Laravel\Facades\Flagsmith;

$enabled = Flagsmith::isEnabled('allow-non-pa-users', [
    'userUuid' => $user->uuid,
    'companyUuid' => $user->company_uuid,
    'membershipType' => $user->active_membership_type,
    'isPrivateAccess' => (string) $user->company?->is_pa,
]);
```

The package uses `userUuid` as the identity identifier when present and also passes it as a trait. You can also pass an explicit context object:

```php
use Flagsmith\Laravel\FlagsmithContext;

$context = FlagsmithContext::make(
    identifier: $user->uuid,
    traits: [
        'companyUuid' => $user->company_uuid,
        'membershipType' => $user->active_membership_type,
    ],
);

Flagsmith::isEnabled('allow-non-pa-users', $context);
```

## Middleware Migration

This Unleash line:

```php
if (! Unleash::isEnabled('allow-non-pa-users') && $user->company->is_pa === false) {
```

becomes:

```php
if (! Flagsmith::isEnabled('allow-non-pa-users') && $user->company->is_pa === false) {
```

With `FLAGSMITH_CONTEXT_PROVIDER` configured, you do not need to pass context every time.

If you want the check to be explicit:

```php
if (
    ! Flagsmith::isEnabled('allow-non-pa-users', [
        'userUuid' => $user->uuid,
        'companyUuid' => $user->company_uuid,
        'membershipType' => $user->active_membership_type,
        'isPrivateAccess' => (string) $user->company?->is_pa,
    ])
    && $user->company->is_pa === false
) {
    // ...
}
```

## Segment Rules

Map Unleash custom context fields to Flagsmith identity traits:

| Unleash field | Flagsmith trait |
| --- | --- |
| `currentUserId` | identity identifier |
| `customContext.companyUuid` | `companyUuid` |
| `customContext.membershipType` | `membershipType` |
| `customContext.isAdmin` | `isAdmin` |
| `customContext.locale` | `locale` |
| `customContext.seatId` | `seatId` |
| `customContext.userUuid` | `userUuid` |
| `customContext.seatKind` | `seatKind` |
| `customContext.isPrivateAccess` | `isPrivateAccess` |

Create Flagsmith segments using these identity trait names. For values that were strings in Unleash, keep casting them to strings during migration so segment comparisons stay predictable.

## Bootstrap Mode

Your Unleash setup used bootstrap data for non-production environments. This package does not add a separate bootstrap feature. The closest options are:

- Use Flagsmith remote/local evaluation normally in every environment.
- Use `FLAGSMITH_OFFLINE_MODE=true` with a custom `Flagsmith\Offline\IOfflineHandler`.
- Set `FLAGSMITH_ENABLED=false` in tests and rely on the facade returning `false` or your supplied default.
