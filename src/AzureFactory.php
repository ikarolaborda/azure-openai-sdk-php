<?php

namespace IkaroLaborda\AzureOpenAI;

use Closure;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr18ClientDiscovery;
use IkaroLaborda\AzureOpenAI\Enums\AzureApiVersion;
use IkaroLaborda\AzureOpenAI\Transporters\AzureRequestHandler;
use OpenAI\Client;
use OpenAI\Transporters\HttpTransporter;
use OpenAI\ValueObjects\ApiKey;
use OpenAI\ValueObjects\Transporter\BaseUri;
use OpenAI\ValueObjects\Transporter\Headers;
use OpenAI\ValueObjects\Transporter\QueryParams;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class AzureFactory
{
    /**
     * The API key for Azure OpenAI authentication.
     */
    private ?string $apiKey = null;

    /**
     * The Azure AD / Entra ID token for bearer authentication.
     */
    private ?string $azureAdToken = null;

    /**
     * A callable that returns a fresh Azure AD token on each invocation.
     */
    private ?Closure $azureAdTokenProvider = null;

    /**
     * The Azure resource name (e.g. 'my-resource').
     */
    private ?string $resource = null;

    /**
     * The full Azure endpoint URL (alternative to resource name).
     */
    private ?string $endpoint = null;

    /**
     * The default deployment name for deployment-scoped operations.
     */
    private ?string $deployment = null;

    /**
     * The Azure OpenAI API version (required).
     */
    private ?string $apiVersion = null;

    /**
     * The HTTP client for the requests.
     */
    private ?ClientInterface $httpClient = null;

    /**
     * The stream handler closure.
     */
    private ?Closure $streamHandler = null;

    /**
     * Additional HTTP headers.
     *
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * Sets the API key for Azure OpenAI.
     * Sent via the `api-key` header.
     */
    public function withApiKey(string $apiKey): self
    {
        $this->apiKey = trim($apiKey);

        return $this;
    }

    /**
     * Sets an Azure AD / Entra ID bearer token for authentication.
     */
    public function withAzureAdToken(string $token): self
    {
        $this->azureAdToken = trim($token);

        return $this;
    }

    /**
     * Sets a callable that returns a fresh Azure AD token on each request.
     * Use this for automatic token rotation in long-running processes.
     */
    public function withAzureAdTokenProvider(Closure $provider): self
    {
        $this->azureAdTokenProvider = $provider;

        return $this;
    }

    /**
     * Sets the Azure resource name. The endpoint will be built as:
     * https://{resource}.openai.azure.com
     */
    public function withResource(string $resource): self
    {
        $this->resource = trim($resource);

        return $this;
    }

    /**
     * Sets the full Azure endpoint URL directly.
     * Use this instead of withResource() when you have a custom endpoint.
     */
    public function withEndpoint(string $endpoint): self
    {
        $this->endpoint = rtrim(trim($endpoint), '/');

        return $this;
    }

    /**
     * Sets the default deployment name for deployment-scoped operations.
     */
    public function withDeployment(string $deployment): self
    {
        $this->deployment = trim($deployment);

        return $this;
    }

    /**
     * Sets the Azure OpenAI API version.
     *
     * @see AzureApiVersion
     */
    public function withApiVersion(string $apiVersion): self
    {
        $this->apiVersion = trim($apiVersion);

        return $this;
    }

    /**
     * Sets a custom HTTP client.
     * If none is provided, PSR-18 HTTP Client Discovery will be used.
     */
    public function withHttpClient(ClientInterface $client): self
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Sets a custom stream handler. Not required when using Guzzle.
     */
    public function withStreamHandler(Closure $streamHandler): self
    {
        $this->streamHandler = $streamHandler;

        return $this;
    }

    /**
     * Adds a custom HTTP header to the requests.
     */
    public function withHttpHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Creates a new Azure OpenAI Client.
     */
    public function make(): Client
    {
        $endpoint = $this->resolveEndpoint();

        $headers = $this->buildHeaders();

        $baseUri = BaseUri::from("{$endpoint}/openai");

        $queryParams = QueryParams::create();
        $queryParams = $queryParams->withParam('api-version', $this->apiVersion ?? throw new Exception(
            'Azure OpenAI requires an API version. Use withApiVersion() to set one.',
        ));

        $innerClient = $this->httpClient ??= Psr18ClientDiscovery::find();

        $azureHandler = new AzureRequestHandler(
            $innerClient,
            $this->deployment,
            $this->azureAdTokenProvider,
        );

        $innerStreamHandler = $this->makeStreamHandler($innerClient);
        $wrappedStreamHandler = fn (RequestInterface $request): ResponseInterface => $innerStreamHandler($azureHandler->prepareRequest($request));

        $transporter = new HttpTransporter($azureHandler, $baseUri, $headers, $queryParams, $wrappedStreamHandler);

        return new Client($transporter);
    }

    /**
     * Resolves the Azure endpoint from resource name or direct endpoint.
     */
    private function resolveEndpoint(): string
    {
        if ($this->endpoint !== null) {
            return (string) preg_replace('#^https?://#', '', $this->endpoint);
        }

        if ($this->resource !== null) {
            return "{$this->resource}.openai.azure.com";
        }

        throw new Exception(
            'Azure OpenAI requires an endpoint. Use withResource() or withEndpoint() to set one.',
        );
    }

    /**
     * Builds the request headers based on the configured authentication method.
     */
    private function buildHeaders(): Headers
    {
        $headers = Headers::create();

        if ($this->apiKey !== null) {
            $headers = $headers->withCustomHeader('api-key', $this->apiKey);
        } elseif ($this->azureAdToken !== null) {
            $headers = Headers::withAuthorization(ApiKey::from($this->azureAdToken));
        }

        // Note: when using azureAdTokenProvider, the token is injected
        // at request time by AzureRequestHandler::prepareRequest().

        foreach ($this->headers as $name => $value) {
            $headers = $headers->withCustomHeader($name, $value);
        }

        return $headers;
    }

    /**
     * Creates a new stream handler for "stream" requests.
     */
    private function makeStreamHandler(ClientInterface $client): Closure
    {
        if (! is_null($this->streamHandler)) {
            return $this->streamHandler;
        }

        if ($client instanceof GuzzleClient) {
            return fn (RequestInterface $request): ResponseInterface => $client->send($request, ['stream' => true]);
        }

        if ($client instanceof Psr18Client) { // @phpstan-ignore-line
            return fn (RequestInterface $request): ResponseInterface => $client->sendRequest($request); // @phpstan-ignore-line
        }

        return function (RequestInterface $_): never {
            throw new Exception('To use stream requests you must provide a stream handler closure via the AzureOpenAI factory.');
        };
    }
}
