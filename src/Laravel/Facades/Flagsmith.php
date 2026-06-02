<?php

declare(strict_types=1);

namespace Flagsmith\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Flagsmith extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'flagsmith';
    }
}
