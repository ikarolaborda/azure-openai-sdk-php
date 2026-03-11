<?php

namespace IkaroLaborda\AzureOpenAI\Facades;

use Illuminate\Support\Facades\Facade;
use OpenAI\Client;

/**
 * @mixin Client
 */
final class AzureOpenAI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
