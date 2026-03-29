<?php

namespace IkaroLaborda\AzureOpenAI;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use OpenAI\Client;

final class AzureOpenAIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the Azure OpenAI client in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/azure-openai.php', 'azure-openai');

        $this->app->singleton(Client::class, function (): Client {
            /** @var \Illuminate\Contracts\Config\Repository $repository */
            $repository = $this->app->make('config');

            /** @var array{api_key: ?string, endpoint: ?string, deployment: ?string, api_version: ?string, use_v1_api: bool} $config */
            $config = $repository->get('azure-openai');

            $factory = AzureOpenAI::factory();

            if ($config['api_key'] !== null && $config['api_key'] !== '') {
                $factory = $factory->withApiKey($config['api_key']);
            }

            if ($config['endpoint'] !== null && $config['endpoint'] !== '') {
                $factory = $factory->withEndpoint($config['endpoint']);
            }

            if ($config['deployment'] !== null && $config['deployment'] !== '') {
                $factory = $factory->withDeployment($config['deployment']);
            }

            if (! empty($config['use_v1_api'])) {
                $factory = $factory->withV1Api();
            } elseif ($config['api_version'] !== null && $config['api_version'] !== '') {
                $factory = $factory->withApiVersion($config['api_version']);
            }

            return $factory->make();
        });

        $this->app->alias(Client::class, 'azure-openai');
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/azure-openai.php' => config_path('azure-openai.php'),
        ], 'azure-openai');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            Client::class,
            'azure-openai',
        ];
    }
}
