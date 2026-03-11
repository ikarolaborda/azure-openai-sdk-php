<?php

namespace IkaroLaborda\AzureOpenAI;

use OpenAI\Client;

final class AzureOpenAI
{
    /**
     * Creates a new Azure OpenAI Client.
     *
     * All parameters are optional — when omitted, values are read from
     * environment variables: AZURE_OPENAI_API_KEY, AZURE_OPENAI_ENDPOINT,
     * AZURE_OPENAI_MODEL_DEPLOYMENT, and AZURE_OPENAI_API_VERSION.
     */
    public static function client(
        ?string $apiKey = null,
        ?string $endpoint = null,
        ?string $deployment = null,
        ?string $apiVersion = null,
    ): Client {
        $factory = self::factory();

        $apiKey ??= getenv('AZURE_OPENAI_API_KEY') ?: null;
        $endpoint ??= getenv('AZURE_OPENAI_ENDPOINT') ?: null;
        $deployment ??= getenv('AZURE_OPENAI_MODEL_DEPLOYMENT') ?: null;
        $apiVersion ??= getenv('AZURE_OPENAI_API_VERSION') ?: null;

        if ($apiKey !== null) {
            $factory = $factory->withApiKey($apiKey);
        }

        if ($endpoint !== null) {
            $factory = $factory->withEndpoint($endpoint);
        }

        if ($deployment !== null) {
            $factory = $factory->withDeployment($deployment);
        }

        if ($apiVersion !== null) {
            $factory = $factory->withApiVersion($apiVersion);
        }

        return $factory->make();
    }

    /**
     * Creates a new factory instance to configure a custom Azure OpenAI Client.
     */
    public static function factory(): AzureFactory
    {
        return new AzureFactory;
    }
}
