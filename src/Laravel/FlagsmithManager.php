<?php

declare(strict_types=1);

namespace Flagsmith\Laravel;

use Flagsmith\Exceptions\FlagsmithThrowable;
use Flagsmith\Flagsmith as FlagsmithClient;
use Flagsmith\Laravel\Contracts\FlagsmithContextProvider;
use Flagsmith\Models\Flags;

class FlagsmithManager
{
    private ?FlagsmithClient $client = null;

    public function __construct(
        private $clientResolver,
        private array $config = [],
        private ?FlagsmithContextProvider $contextProvider = null,
    ) {
    }

    public function isEnabled(string $featureName, $context = null, bool $default = false): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        try {
            return $this->flags($context)->isFeatureEnabled($featureName);
        } catch (FlagsmithThrowable) {
            return $default;
        }
    }

    public function getValue(string $featureName, $context = null, $default = null)
    {
        if (!$this->enabled()) {
            return $default;
        }

        try {
            return $this->flags($context)->getFeatureValue($featureName);
        } catch (FlagsmithThrowable) {
            return $default;
        }
    }

    public function flags($context = null): Flags
    {
        $context = $this->resolveContext($context);

        if ($context->hasIdentity()) {
            return $this->client()->getIdentityFlags(
                $context->identifier(),
                $context->traits(),
                $context->transient(),
            );
        }

        return $this->client()->getEnvironmentFlags();
    }

    public function updateEnvironment(): void
    {
        $this->client()->updateEnvironment();
    }

    public function raw(): FlagsmithClient
    {
        return $this->client();
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    public function context($context = null): FlagsmithContext
    {
        return $this->resolveContext($context);
    }

    public function __call(string $method, array $parameters)
    {
        return $this->client()->{$method}(...$parameters);
    }

    private function client(): FlagsmithClient
    {
        if ($this->client === null) {
            $client = ($this->clientResolver)();

            if (!$client instanceof FlagsmithClient) {
                throw new \RuntimeException('The Flagsmith client resolver must return an instance of ' . FlagsmithClient::class . '.');
            }

            $this->client = $client;
        }

        return $this->client;
    }

    private function resolveContext($context): FlagsmithContext
    {
        if ($context === null && $this->contextProvider !== null) {
            $context = $this->contextProvider->getContext();
        }

        if ($context instanceof FlagsmithContext) {
            return $context;
        }

        if (is_string($context) || is_int($context)) {
            return FlagsmithContext::make((string) $context);
        }

        if (is_array($context)) {
            return FlagsmithContext::fromArray($context);
        }

        if (is_object($context)) {
            return $this->contextFromObject($context);
        }

        return FlagsmithContext::make();
    }

    private function contextFromObject(object $context): FlagsmithContext
    {
        $customContext = $this->objectValue($context, ['customContext', 'custom_context']);
        $traits = is_array($customContext) || is_object($customContext) ? (array) $customContext : [];

        foreach (['ipAddress', 'sessionId', 'hostname', 'environment', 'currentTime'] as $key) {
            $value = $this->objectValue($context, [$key]);
            if ($value !== null && !array_key_exists($key, $traits)) {
                $traits[$key] = $value;
            }
        }

        $identifier = $this->objectValue($context, [
            'identifier',
            'userUuid',
            'user_uuid',
            'userId',
            'user_id',
            'currentUserId',
            'current_user_id',
        ]);

        if (empty($traits)) {
            return FlagsmithContext::fromArray((array) $context);
        }

        return FlagsmithContext::make(
            identifier: $identifier !== null ? (string) $identifier : null,
            traits: $traits,
            transient: (bool) ($this->objectValue($context, ['transient']) ?? false),
        );
    }

    private function objectValue(object $object, array $names)
    {
        foreach ($names as $name) {
            if (isset($object->{$name})) {
                return $object->{$name};
            }

            $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
            if (method_exists($object, $getter)) {
                return $object->{$getter}();
            }

            if (method_exists($object, $name)) {
                return $object->{$name}();
            }
        }

        return null;
    }
}
