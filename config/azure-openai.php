<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Azure OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your Azure OpenAI API key, found in the Azure Portal under your
    | resource's "Keys and Endpoint" section.
    |
    */

    'api_key' => env('AZURE_OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Azure OpenAI Endpoint
    |--------------------------------------------------------------------------
    |
    | The full URL of your Azure OpenAI resource endpoint, e.g.:
    | https://your-resource-name.openai.azure.com
    |
    */

    'endpoint' => env('AZURE_OPENAI_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Default Deployment
    |--------------------------------------------------------------------------
    |
    | The name of your default model deployment. This determines which model
    | is used for deployment-scoped operations like chat completions,
    | embeddings, and image generation.
    |
    */

    'deployment' => env('AZURE_OPENAI_MODEL_DEPLOYMENT'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The Azure OpenAI API version to use. Check the Azure documentation
    | for available versions. Stable versions are recommended for production.
    | Ignored when use_v1_api is enabled.
    |
    | @see \IkaroLaborda\AzureOpenAI\Enums\AzureApiVersion
    |
    */

    'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-10-21'),

    /*
    |--------------------------------------------------------------------------
    | Use v1 API
    |--------------------------------------------------------------------------
    |
    | Enable the Azure OpenAI v1 API (available since August 2025).
    | When enabled, the client uses /openai/v1/ as the base path,
    | removes the need for api-version parameters, and the model
    | deployment is specified in the request body instead of the URL.
    |
    | @see https://learn.microsoft.com/en-us/azure/foundry/openai/api-version-lifecycle
    |
    */

    'use_v1_api' => env('AZURE_OPENAI_V1_API', false),

];
