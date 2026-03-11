<?php

use IkaroLaborda\AzureOpenAI\AzureOpenAI;
use OpenAI\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

it('may create an azure client with explicit params', function () {
    $client = AzureOpenAI::client(
        apiKey: 'test-key',
        endpoint: 'https://my-resource.openai.azure.com',
        deployment: 'gpt-4o',
        apiVersion: '2024-10-21',
    );

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client from environment variables', function () {
    putenv('AZURE_OPENAI_API_KEY=env-key');
    putenv('AZURE_OPENAI_ENDPOINT=https://env-resource.openai.azure.com');
    putenv('AZURE_OPENAI_MODEL_DEPLOYMENT=gpt-4o');
    putenv('AZURE_OPENAI_API_VERSION=2024-10-21');

    $client = AzureOpenAI::client();

    expect($client)->toBeInstanceOf(Client::class);

    putenv('AZURE_OPENAI_API_KEY');
    putenv('AZURE_OPENAI_ENDPOINT');
    putenv('AZURE_OPENAI_MODEL_DEPLOYMENT');
    putenv('AZURE_OPENAI_API_VERSION');
});

it('explicit params take precedence over env vars', function () {
    putenv('AZURE_OPENAI_API_KEY=env-key');
    putenv('AZURE_OPENAI_ENDPOINT=https://env-resource.openai.azure.com');
    putenv('AZURE_OPENAI_MODEL_DEPLOYMENT=env-deployment');
    putenv('AZURE_OPENAI_API_VERSION=2024-06-01');

    $client = AzureOpenAI::client(
        apiKey: 'explicit-key',
        endpoint: 'https://explicit-resource.openai.azure.com',
        deployment: 'explicit-deployment',
        apiVersion: '2024-10-21',
    );

    expect($client)->toBeInstanceOf(Client::class);

    putenv('AZURE_OPENAI_API_KEY');
    putenv('AZURE_OPENAI_ENDPOINT');
    putenv('AZURE_OPENAI_MODEL_DEPLOYMENT');
    putenv('AZURE_OPENAI_API_VERSION');
});

it('may create an azure client via factory', function () {
    $client = AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withResource('my-resource')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client with custom endpoint', function () {
    $client = AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withEndpoint('https://custom-endpoint.openai.azure.com')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client with azure ad token', function () {
    $client = AzureOpenAI::factory()
        ->withAzureAdToken('ad-token')
        ->withResource('my-resource')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client with azure ad token provider', function () {
    $client = AzureOpenAI::factory()
        ->withAzureAdTokenProvider(fn () => 'fresh-token')
        ->withResource('my-resource')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client without deployment for account-scoped operations', function () {
    $client = AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withResource('my-resource')
        ->withApiVersion('2024-10-21')
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client with custom http client', function () {
    $client = AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withResource('my-resource')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->withHttpClient(new GuzzleHttp\Client)
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client with custom headers', function () {
    $client = AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withResource('my-resource')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->withHttpHeader('X-Custom', 'value')
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('may create an azure client with custom stream handler', function () {
    $guzzle = new GuzzleHttp\Client;

    $client = AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withResource('my-resource')
        ->withDeployment('gpt-4o')
        ->withApiVersion('2024-10-21')
        ->withHttpClient($guzzle)
        ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $guzzle->send($request, ['stream' => true]))
        ->make();

    expect($client)->toBeInstanceOf(Client::class);
});

it('throws when no endpoint or resource is provided', function () {
    AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withApiVersion('2024-10-21')
        ->make();
})->throws(Exception::class, 'Azure OpenAI requires an endpoint');

it('throws when no api version is provided', function () {
    AzureOpenAI::factory()
        ->withApiKey('test-key')
        ->withResource('my-resource')
        ->make();
})->throws(Exception::class, 'Azure OpenAI requires an API version');
