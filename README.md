# Azure OpenAI SDK for PHP

A PHP package that brings first-class [Azure OpenAI Service](https://learn.microsoft.com/en-us/azure/ai-services/openai/) support to your applications. Built on top of [openai-php/client](https://github.com/openai-php/client) — the excellent community-maintained PHP client created by [Nuno Maduro](https://github.com/nunomaduro) and contributors.

This package handles Azure-specific deployment-based URL routing, API versioning, and authentication (API keys and Azure AD / Entra ID) so you can work with Azure OpenAI the same way you work with the standard OpenAI API.

## Table of Contents
- [Installation](#installation)
- [Configuration](#configuration)
  - [Laravel](#laravel)
  - [Standalone (non-Laravel)](#standalone-non-laravel)
- [Usage](#usage)
  - [Chat Completions](#chat-completions)
  - [Streaming](#streaming)
  - [Embeddings](#embeddings)
  - [Multiple Deployments](#multiple-deployments)
  - [Account-Scoped Operations](#account-scoped-operations)
- [Authentication](#authentication)
  - [API Key](#api-key)
  - [Azure AD / Entra ID Token](#azure-ad--entra-id-token)
  - [Azure AD Token Provider](#azure-ad-token-provider)
- [API Versions](#api-versions)
- [Available Resources](#available-resources)

## Installation

> **Requires [PHP 8.2+](https://www.php.net/releases/)**

```bash
composer require ikarolaborda/azure-openai-sdk-php
```

If your project doesn't already have a PSR-18 HTTP client, install one:

```bash
composer require guzzlehttp/guzzle
```

## Configuration

### Laravel

The package auto-discovers its service provider and facade. Publish the config file:

```bash
php artisan vendor:publish --tag=azure-openai
```

This creates `config/azure-openai.php`. Then add your credentials to `.env`:

```env
AZURE_OPENAI_API_KEY=your-azure-api-key
AZURE_OPENAI_ENDPOINT=https://your-resource.openai.azure.com
AZURE_OPENAI_MODEL_DEPLOYMENT=gpt-4o
AZURE_OPENAI_API_VERSION=2024-10-21
```

Now you can use the facade anywhere in your application:

```php
use IkaroLaborda\AzureOpenAI\Facades\AzureOpenAI;

$response = AzureOpenAI::chat()->create([
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

echo $response->choices[0]->message->content;
```

Or resolve the client from the container:

```php
use OpenAI\Client;

$client = app(Client::class);

$response = $client->chat()->create([
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);
```

### Standalone (non-Laravel)

Without Laravel, configure the client directly. Set your environment variables and call `client()` with no arguments:

```php
use IkaroLaborda\AzureOpenAI\AzureOpenAI;

// Reads from AZURE_OPENAI_API_KEY, AZURE_OPENAI_ENDPOINT,
// AZURE_OPENAI_MODEL_DEPLOYMENT, and AZURE_OPENAI_API_VERSION
$client = AzureOpenAI::client();
```

Or pass parameters explicitly — any argument takes precedence over its environment variable:

```php
$client = AzureOpenAI::client(
    apiKey: 'your-azure-api-key',
    endpoint: 'https://your-resource.openai.azure.com',
    deployment: 'gpt-4o',
    apiVersion: '2024-10-21',
);
```

For full control, use the factory:

```php
$client = AzureOpenAI::factory()
    ->withApiKey('your-azure-api-key')
    ->withEndpoint('https://your-resource.openai.azure.com')
    ->withDeployment('gpt-4o')
    ->withApiVersion('2024-10-21')
    ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 120]))
    ->make();
```

If you prefer using the resource name instead of the full endpoint URL:

```php
$client = AzureOpenAI::factory()
    ->withApiKey('your-azure-api-key')
    ->withResource('your-resource-name')     // builds https://your-resource-name.openai.azure.com
    ->withDeployment('gpt-4o')
    ->withApiVersion('2024-10-21')
    ->make();
```

## Usage

Because Azure routes requests based on the deployment name in the URL, you don't need to pass a `model` parameter in your calls — it's determined by the deployment you configured.

### Chat Completions

```php
$response = $client->chat()->create([
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'What is the capital of France?'],
    ],
]);

echo $response->choices[0]->message->content;
```

### Streaming

```php
$stream = $client->chat()->createStreamed([
    'messages' => [
        ['role' => 'user', 'content' => 'Write a short poem about PHP.'],
    ],
]);

foreach ($stream as $response) {
    echo $response->choices[0]->delta->content;
}
```

### Embeddings

```php
$response = $client->embeddings()->create([
    'input' => 'The quick brown fox jumps over the lazy dog.',
]);

$vector = $response->embeddings[0]->embedding; // array of floats
```

### Multiple Deployments

Each client targets a single deployment. If your application uses multiple models, create a client for each:

```php
use IkaroLaborda\AzureOpenAI\AzureOpenAI;

// Both clients share the API key and endpoint from env;
// only the deployment differs.
$chatClient = AzureOpenAI::client(deployment: 'gpt-4o');
$embeddingClient = AzureOpenAI::client(deployment: 'text-embedding-3-small');

$chat = $chatClient->chat()->create([
    'messages' => [['role' => 'user', 'content' => 'Summarize this document.']],
]);

$embedding = $embeddingClient->embeddings()->create([
    'input' => 'The quick brown fox jumps over the lazy dog.',
]);
```

### Account-Scoped Operations

Some Azure OpenAI operations — like listing models or managing files — don't require a deployment. You can omit the deployment for these:

```php
$client = AzureOpenAI::factory()
    ->withApiKey('your-azure-api-key')
    ->withEndpoint('https://your-resource.openai.azure.com')
    ->withApiVersion('2024-10-21')
    ->make();

$models = $client->models()->list();
```

## Authentication

### API Key

The most common method. Your key is sent via the `api-key` header:

```php
$client = AzureOpenAI::factory()
    ->withApiKey('your-azure-api-key')
    ->withEndpoint('https://your-resource.openai.azure.com')
    ->withDeployment('gpt-4o')
    ->withApiVersion('2024-10-21')
    ->make();
```

### Azure AD / Entra ID Token

For production workloads, Azure AD tokens are recommended over API keys:

```php
$client = AzureOpenAI::factory()
    ->withAzureAdToken($token)
    ->withEndpoint('https://your-resource.openai.azure.com')
    ->withDeployment('gpt-4o')
    ->withApiVersion('2024-10-21')
    ->make();
```

### Azure AD Token Provider

For long-running processes where tokens expire, pass a callable that fetches a fresh token on each request:

```php
$client = AzureOpenAI::factory()
    ->withAzureAdTokenProvider(function () use ($credential) {
        $token = $credential->getToken('https://cognitiveservices.azure.com/.default');

        return $token->token;
    })
    ->withEndpoint('https://your-resource.openai.azure.com')
    ->withDeployment('gpt-4o')
    ->withApiVersion('2024-10-21')
    ->make();
```

## API Versions

Azure OpenAI requires an explicit API version on every request. The package provides an enum with known versions:

```php
use IkaroLaborda\AzureOpenAI\Enums\AzureApiVersion;

$client = AzureOpenAI::factory()
    ->withApiKey('your-azure-api-key')
    ->withEndpoint('https://your-resource.openai.azure.com')
    ->withDeployment('gpt-4o')
    ->withApiVersion(AzureApiVersion::V2024_10_21->value)
    ->make();
```

| Enum Case | Value |
|-----------|-------|
| `V2024_06_01` | `2024-06-01` |
| `V2024_10_21` | `2024-10-21` |
| `V2025_01_01_PREVIEW` | `2025-01-01-preview` |
| `V2025_03_01_PREVIEW` | `2025-03-01-preview` |

## Available Resources

This package gives you access to all resources provided by [openai-php/client](https://github.com/openai-php/client), routed through Azure's deployment-based endpoints. Deployment-scoped resources (chat, completions, embeddings, images, audio, responses) are automatically routed via `/deployments/{deployment}/`. Account-scoped resources (models, files, fine-tuning, assistants, threads, batches, vector stores, moderations) are routed directly under `/openai/`.

For full resource documentation, see the [openai-php/client README](https://github.com/openai-php/client#usage).

---

Built with care by [Ikaro C. Laborda](https://github.com/ikarolaborda). Licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.
