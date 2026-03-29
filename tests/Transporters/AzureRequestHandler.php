<?php

use GuzzleHttp\Psr7\Response;
use IkaroLaborda\AzureOpenAI\Transporters\AzureRequestHandler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

function createMockRequest(string $path): RequestInterface
{
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn($path);
    $uri->shouldReceive('withPath')->andReturnUsing(function (string $newPath) {
        $newUri = Mockery::mock(UriInterface::class);
        $newUri->shouldReceive('getPath')->andReturn($newPath);

        return $newUri;
    });

    $request = Mockery::mock(RequestInterface::class);
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('withUri')->andReturnUsing(function ($newUri) {
        $newRequest = Mockery::mock(RequestInterface::class);
        $newRequest->shouldReceive('getUri')->andReturn($newUri);
        $newRequest->shouldReceive('withHeader')->andReturn($newRequest);

        return $newRequest;
    });
    $request->shouldReceive('withHeader')->andReturn($request);

    return $request;
}

function createJsonResponse(array $data): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
}

it('rewrites deployment-scoped chat completions URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/gpt-4o/chat/completions';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/chat/completions');

    $result = $handler->sendRequest($request);

    expect($result)->toBe($response);
});

it('rewrites deployment-scoped embeddings URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/text-embedding/embeddings';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'text-embedding');
    $request = createMockRequest('/openai/embeddings');

    $handler->sendRequest($request);
});

it('rewrites deployment-scoped completions URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/gpt-35/completions';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-35');
    $request = createMockRequest('/openai/completions');

    $handler->sendRequest($request);
});

it('rewrites deployment-scoped images URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/dall-e-3/images/generations';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'dall-e-3');
    $request = createMockRequest('/openai/images/generations');

    $handler->sendRequest($request);
});

it('rewrites deployment-scoped audio URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/whisper/audio/transcriptions';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'whisper');
    $request = createMockRequest('/openai/audio/transcriptions');

    $handler->sendRequest($request);
});

it('rewrites deployment-scoped responses URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/gpt-4o/responses';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/responses');

    $handler->sendRequest($request);
});

it('does not rewrite account-scoped models URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = createJsonResponse(['object' => 'list', 'data' => []]);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/models';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/models');

    $handler->sendRequest($request);
});

it('does not rewrite account-scoped files URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/files';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/files');

    $handler->sendRequest($request);
});

it('does not rewrite account-scoped fine tuning URL', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/fine_tuning/jobs';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/fine_tuning/jobs');

    $handler->sendRequest($request);
});

it('does not rewrite when no deployment is set', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/chat/completions';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient);
    $request = createMockRequest('/openai/chat/completions');

    $handler->sendRequest($request);
});

it('does not double-inject deployments prefix', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/deployments/gpt-4o/chat/completions';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/deployments/gpt-4o/chat/completions');

    $handler->sendRequest($request);
});

it('injects azure ad token from provider', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = createJsonResponse(['object' => 'list', 'data' => []]);

    $innerClient->shouldReceive('sendRequest')->once()->andReturn($response);

    $tokenCalled = false;
    $handler = new AzureRequestHandler($innerClient, null, function () use (&$tokenCalled) {
        $tokenCalled = true;

        return 'fresh-ad-token';
    });

    $request = createMockRequest('/openai/models');
    $handler->sendRequest($request);

    expect($tokenCalled)->toBeTrue();
});

it('prepareRequest can be used independently for stream handler wrapping', function () {
    $innerClient = Mockery::mock(ClientInterface::class);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/chat/completions');

    $prepared = $handler->prepareRequest($request);

    expect($prepared->getUri()->getPath())->toBe('/openai/deployments/gpt-4o/chat/completions');
});

it('normalizes models list response from Azure format to OpenAI format', function () {
    $innerClient = Mockery::mock(ClientInterface::class);

    $azureResponse = createJsonResponse([
        'object' => 'list',
        'data' => [
            [
                'id' => 'gpt-4o',
                'object' => 'model',
                'created_at' => 1715558400,
                'status' => 'succeeded',
                'capabilities' => ['chat_completion' => true],
            ],
            [
                'id' => 'text-embedding-3-small',
                'object' => 'model',
                'created_at' => 1706140800,
                'status' => 'succeeded',
            ],
        ],
    ]);

    $innerClient->shouldReceive('sendRequest')->once()->andReturn($azureResponse);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/models');

    $result = $handler->sendRequest($request);
    $body = json_decode((string) $result->getBody(), true);

    // Verify created_at was mapped to created
    expect($body['data'][0]['created'])->toBe(1715558400);
    expect($body['data'][1]['created'])->toBe(1706140800);

    // Verify created_at was removed
    expect($body['data'][0])->not->toHaveKey('created_at');

    // Verify owned_by default was added
    expect($body['data'][0]['owned_by'])->toBe('azure');
    expect($body['data'][1]['owned_by'])->toBe('azure');
});

it('normalizes single model retrieve response from Azure format', function () {
    $innerClient = Mockery::mock(ClientInterface::class);

    $azureResponse = createJsonResponse([
        'id' => 'gpt-4o',
        'object' => 'model',
        'created_at' => 1715558400,
        'status' => 'succeeded',
    ]);

    $innerClient->shouldReceive('sendRequest')->once()->andReturn($azureResponse);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/models/gpt-4o');

    $result = $handler->sendRequest($request);
    $body = json_decode((string) $result->getBody(), true);

    expect($body['created'])->toBe(1715558400);
    expect($body)->not->toHaveKey('created_at');
    expect($body['owned_by'])->toBe('azure');
});

it('does not normalize non-models responses', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')->once()->andReturn($response);

    $handler = new AzureRequestHandler($innerClient);
    $request = createMockRequest('/openai/chat/completions');

    $result = $handler->sendRequest($request);

    // Response should pass through untouched
    expect($result)->toBe($response);
});

it('v1 api mode does not rewrite chat completions URL when no deployment', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/v1/chat/completions';
        })
        ->andReturn($response);

    // v1 API mode: no deployment (null), model goes in request body
    $handler = new AzureRequestHandler($innerClient);
    $request = createMockRequest('/openai/v1/chat/completions');

    $result = $handler->sendRequest($request);

    expect($result)->toBe($response);
});

it('v1 api mode does not rewrite responses URL when no deployment', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')
        ->once()
        ->withArgs(function (RequestInterface $request) {
            return $request->getUri()->getPath() === '/openai/v1/responses';
        })
        ->andReturn($response);

    $handler = new AzureRequestHandler($innerClient);
    $request = createMockRequest('/openai/v1/responses');

    $result = $handler->sendRequest($request);

    expect($result)->toBe($response);
});

it('v1 api mode still injects azure ad token from provider', function () {
    $innerClient = Mockery::mock(ClientInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $innerClient->shouldReceive('sendRequest')->once()->andReturn($response);

    $tokenCalled = false;
    $handler = new AzureRequestHandler($innerClient, null, function () use (&$tokenCalled) {
        $tokenCalled = true;

        return 'fresh-v1-token';
    });

    $request = createMockRequest('/openai/v1/chat/completions');
    $handler->sendRequest($request);

    expect($tokenCalled)->toBeTrue();
});

it('preserves existing OpenAI format fields during normalization', function () {
    $innerClient = Mockery::mock(ClientInterface::class);

    // Response that already has 'created' and 'owned_by' (OpenAI format)
    $openaiResponse = createJsonResponse([
        'object' => 'list',
        'data' => [
            [
                'id' => 'gpt-4o',
                'object' => 'model',
                'created' => 1715558400,
                'owned_by' => 'openai',
            ],
        ],
    ]);

    $innerClient->shouldReceive('sendRequest')->once()->andReturn($openaiResponse);

    $handler = new AzureRequestHandler($innerClient, 'gpt-4o');
    $request = createMockRequest('/openai/models');

    $result = $handler->sendRequest($request);
    $body = json_decode((string) $result->getBody(), true);

    // Should not overwrite existing fields
    expect($body['data'][0]['created'])->toBe(1715558400);
    expect($body['data'][0]['owned_by'])->toBe('openai');
});
