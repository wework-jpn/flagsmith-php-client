<?php

declare(strict_types=1);

namespace Flagsmith\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled(string $featureName, mixed $context = null, bool $default = false)
 * @method static mixed getValue(string $featureName, mixed $context = null, mixed $default = null)
 * @method static \Flagsmith\Models\Flags flags(mixed $context = null)
 * @method static void updateEnvironment()
 * @method static \Flagsmith\Flagsmith raw()
 * @method static bool enabled()
 * @method static \Flagsmith\Laravel\FlagsmithContext context(mixed $context = null)
 *
 * @see \Flagsmith\Laravel\FlagsmithManager
 */
class Flagsmith extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'flagsmith';
    }
}
