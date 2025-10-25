<?php
namespace Friezer\CasOauth;

use Illuminate\Support\ServiceProvider as IServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ServiceProvider extends IServiceProvider
{
    private function requirements(): bool
    {
        return config('cas-oauth.provider')
            && config('cas-oauth.client_id')
            && config('cas-oauth.client_secret')
            && !empty(config('services.cas', []));
    }

    public function boot(): void
    {
        if (!$this->requirements()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/cas-oauth.php' => config_path('cas-oauth.php'),
        ], 'cas-oauth-config');

        $this->loadRoutesFrom(__DIR__ . '/../resources/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../resources/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cas-oauth');

        $provider = config('cas-oauth.provider');
        
        config()->set("services.{$provider}", [
            'client_id' => config('cas-oauth.client_id'),
            'client_secret' => config('cas-oauth.client_secret'),
            'redirect' => url('/oauth/callback'),
        ]);

        $this->registerSocialiteProvider($provider);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cas-oauth.php',
            'cas-oauth'
        );

        if (!$this->requirements()) {
            return;
        }

        $this->app->register(\SocialiteProviders\Manager\ServiceProvider::class);
    }

    private function registerSocialiteProvider(string $provider): void
    {
        $providerClass = Str::studly($provider);
        $listenerClass = "\\SocialiteProviders\\{$providerClass}\\{$providerClass}ExtendSocialite";

        if (!class_exists($listenerClass)) {
            throw new \RuntimeException(
                "Provider class {$listenerClass} not found. " .
                "Make sure you have installed the correct SocialiteProviders package."
            );
        }

        Event::listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            "{$listenerClass}@handle"
        );
    }
}