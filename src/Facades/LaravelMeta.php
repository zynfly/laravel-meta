<?php

namespace Zynfly\LaravelMeta\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Zynfly\LaravelMeta\LaravelMeta
 */
class LaravelMeta extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zynfly\LaravelMeta\LaravelMeta::class;
    }
}
