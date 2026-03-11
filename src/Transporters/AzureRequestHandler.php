<?php

namespace IkaroLaborda\AzureOpenAI\Transporters;

use Closure;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client decorator that rewrites request URLs for Azure OpenAI.
 *
 * Deployment-scoped operations get `/deployments/{deployment}/` injected into
 * the URL path. Account-scoped operations (models, files, etc.) pass through
 * unchanged. When an Azure AD token provider is configured, the Authorization
 * header is refreshed on every request.
 *
 * Azure responses are normalized to match the OpenAI API format expected by
 * the upstream openai-php/client library.
 *
 * @internal
 */
final class AzureRequestHandler implements ClientInterface
{
    /**
     * Resource path prefixes that require a deployment in the URL.
     *
     * @var list<string>
     */
    private const DEPLOYMENT_PREFIXES = [
        'chat/',
        'completions',
        'embeddings',
        'images/',
        'audio/',
        'responses',
    ];

    /**
     * Azure-to-OpenAI field mappings for response normalization.
     * Each entry maps an Azure field name to its OpenAI equivalent.
     *
     * @var array<string, string>
     */
    private const FIELD_MAPPINGS = [
        'created_at' => 'created',
    ];

    /**
     * Default values for fields that Azure omits but OpenAI requires.
     *
     * @var array<string, string>
     */
    private const FIELD_DEFAULTS = [
        'owned_by' => 'azure',
    ];

    /**
     * Creates a new Azure request handler instance.
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly ?string $deployment = null,
        private readonly ?Closure $tokenProvider = null,
    ) {
        // ..
    }

    /**
     * Sends the request after applying Azure-specific URL rewriting and auth.
     * Normalizes the response to match the OpenAI API format.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $prepared = $this->prepareRequest($request);
        $response = $this->client->sendRequest($prepared);

        return $this->normalizeResponse($response, $prepared);
    }

    /**
     * Prepares a request with Azure URL rewriting and token injection.
     *
     * This method is public so it can be reused by the stream handler wrapper.
     */
    public function prepareRequest(RequestInterface $request): RequestInterface
    {
        $request = $this->rewriteUrl($request);

        if ($this->tokenProvider !== null) {
            $token = ($this->tokenProvider)();
            $request = $request->withHeader('Authorization', "Bearer {$token}");
        }

        return $request;
    }

    /**
     * Rewrites the request URL to inject the deployment path segment
     * for deployment-scoped operations.
     */
    private function rewriteUrl(RequestInterface $request): RequestInterface
    {
        if ($this->deployment === null) {
            return $request;
        }

        $uri = $request->getUri();
        $path = $uri->getPath();

        // Find the /openai/ segment and extract the operation path after it
        $openaiPrefix = '/openai/';
        $prefixPos = strpos($path, $openaiPrefix);

        if ($prefixPos === false) {
            return $request;
        }

        $operationPath = substr($path, $prefixPos + strlen($openaiPrefix));

        // Already rewritten — don't double-inject
        if (str_starts_with($operationPath, 'deployments/')) {
            return $request;
        }

        if (! $this->isDeploymentScoped($operationPath)) {
            return $request;
        }

        $newPath = substr($path, 0, $prefixPos)
            .$openaiPrefix
            .'deployments/'.$this->deployment.'/'
            .$operationPath;

        return $request->withUri($uri->withPath($newPath));
    }

    /**
     * Determines whether the given operation path requires a deployment in the URL.
     */
    private function isDeploymentScoped(string $operationPath): bool
    {
        foreach (self::DEPLOYMENT_PREFIXES as $prefix) {
            if (str_starts_with($operationPath, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalizes an Azure response to match the OpenAI API format.
     */
    private function normalizeResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (! $this->needsResponseNormalization($path)) {
            return $response;
        }

        $body = (string) $response->getBody();

        /** @var array<string, mixed>|null $data */
        $data = json_decode($body, true);

        if (! is_array($data)) {
            return $response;
        }

        $normalized = $this->normalizeData($data);
        $json = json_encode($normalized, JSON_THROW_ON_ERROR);

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        return $response->withBody($streamFactory->createStream($json));
    }

    /**
     * Determines whether the response at the given path needs normalization.
     */
    private function needsResponseNormalization(string $path): bool
    {
        return (bool) preg_match('#/openai/(models|deployments/[^/]+/models)#', $path);
    }

    /**
     * Recursively normalizes Azure response data to OpenAI format.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeData(array $data): array
    {
        // Normalize a list response (e.g., models list)
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = array_map(
                fn (array $item): array => $this->normalizeItem($item),
                $data['data'],
            );

            return $data;
        }

        // Normalize a single object response (e.g., model retrieve)
        return $this->normalizeItem($data);
    }

    /**
     * Normalizes a single response item by mapping Azure fields to OpenAI fields.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item): array
    {
        // Apply field mappings (rename Azure keys to OpenAI keys)
        foreach (self::FIELD_MAPPINGS as $azureKey => $openaiKey) {
            if (array_key_exists($azureKey, $item) && ! array_key_exists($openaiKey, $item)) {
                $item[$openaiKey] = $item[$azureKey];
                unset($item[$azureKey]);
            }
        }

        // Apply default values for missing required fields
        foreach (self::FIELD_DEFAULTS as $key => $default) {
            if (! array_key_exists($key, $item)) {
                $item[$key] = $default;
            }
        }

        return $item;
    }
}
