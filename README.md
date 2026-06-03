# Flagsmith Laravel Client

Laravel integration for the Flagsmith PHP client.

## Installation

This package is not published on Packagist, so add it to your application's `composer.json` as a VCS repository before requiring it.

The Composer package name is `wework-japan/flagsmith-laravel-client`, but Composer must be configured with the GitHub repository URL that hosts the source code:

```bash
composer config repositories.flagsmith-laravel-client vcs https://github.com/wework-jpn/flagsmith-laravel-client.git
```

If Composer cannot access the GitHub API because of local SSL certificate issues, use SSH and disable GitHub API metadata lookups:

```bash
composer config repositories.flagsmith-laravel-client '{"type":"vcs","url":"git@github.com:wework-jpn/flagsmith-laravel-client.git","no-api":true}'
```

Install the latest code from the `main` branch:

```bash
composer require wework-japan/flagsmith-laravel-client:dev-main
```

The `main` branch must already contain a `composer.json` whose `name` is `wework-japan/flagsmith-laravel-client`.

Or install a specific tagged release:

```bash
composer require wework-japan/flagsmith-laravel-client:v1.0.0
```

Replace `v1.0.0` with the tag you want to install.

The tag must point to a commit whose `composer.json` uses the same package name.

## Updating

If your application requires `dev-main`, update to the latest commit on the `main` branch with:

```bash
composer update wework-japan/flagsmith-laravel-client
```

To move to a different tag, require the new tag explicitly:

```bash
composer require wework-japan/flagsmith-laravel-client:v1.0.1
```

Composer will update `composer.json` and `composer.lock` in your application to use that tag.
