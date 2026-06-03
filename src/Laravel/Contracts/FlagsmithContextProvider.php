<?php

declare(strict_types=1);

namespace Flagsmith\Laravel\Contracts;

interface FlagsmithContextProvider
{
    public function getContext();
}
