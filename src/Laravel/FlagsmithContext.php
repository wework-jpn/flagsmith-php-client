<?php

declare(strict_types=1);

namespace Flagsmith\Laravel;

final class FlagsmithContext
{
    private ?string $identifier;
    private object $traits;
    private bool $transient;

    public function __construct(?string $identifier = null, array|object $traits = [], bool $transient = false)
    {
        $this->identifier = $identifier;
        $this->traits = (object) $traits;
        $this->transient = $transient;
    }

    public static function make(?string $identifier = null, array|object $traits = [], bool $transient = false): self
    {
        return new self($identifier, $traits, $transient);
    }

    public static function fromArray(array $context): self
    {
        $identifier = self::firstStringValue($context, [
            'identifier',
            'userUuid',
            'user_uuid',
            'userId',
            'user_id',
            'currentUserId',
            'current_user_id',
        ]);

        $traits = $context['traits'] ?? array_diff_key($context, [
            'identifier' => true,
            'traits' => true,
            'transient' => true,
        ]);

        return new self(
            identifier: $identifier,
            traits: is_array($traits) || is_object($traits) ? $traits : [],
            transient: (bool) ($context['transient'] ?? false),
        );
    }

    public function identifier(): ?string
    {
        return $this->identifier;
    }

    public function traits(): object
    {
        return $this->traits;
    }

    public function transient(): bool
    {
        return $this->transient;
    }

    public function hasIdentity(): bool
    {
        return $this->identifier !== null && $this->identifier !== '';
    }

    private static function firstStringValue(array $context, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($context[$key]) && $context[$key] !== '') {
                return (string) $context[$key];
            }
        }

        return null;
    }
}
