<?php

namespace Micorksen\CasOauth;

use Illuminate\Support\ServiceProvider as IServiceProvider;
use Illuminate\Support\Facades\Event;

class ServiceProvider extends IServiceProvider
{
    /**
     * Checks whether requirements are matched.
     * 
     * @return bool
     */
    private function requirements(): bool
    {
        return env('OAUTH_PROVIDER')
            && env('OAUTH_CLIENT_ID')
            && env('OAUTH_CLIENT_SECRET')
            && (config('services.cas', []) !== []);
    }

    /**
     * Registers any others events for your application.
     *
     * @return void
     */
    public function boot()
    {
        $provider = env('OAUTH_PROVIDER');
        if (!$this->requirements()) {
            return;
        }

        config()->set('services.' . $provider, [
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'redirect' => url('/oauth/callback'),
        ]);

        Event::listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, '\SocialiteProviders\\' . ucfirst($provider) . '\\' . ucfirst($provider) . 'ExtendSocialite@handle');
    }

    /**
     * Registers the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->requirements()) {
            return;
        }

        $this->app->register('SocialiteProviders\Manager\ServiceProvider');
        $this->loadRoutesFrom(__DIR__ . '/../resources/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../resources/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cas-oauth');
    }
}
