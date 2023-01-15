<?php

namespace Micorksen\CasOauth;

use Illuminate\Support\ServiceProvider as IServiceProvider;
use Illuminate\Support\Facades\Event;

class ServiceProvider extends IServiceProvider
{
  /**
   * Check that the requirements are configured.
   *
   * @return bool
   */
  private function requirements(): bool
  {
    return env('CAS_TICKET_PREFIX')
           && env('OAUTH_PROVIDER')
           && env('OAUTH_CLIENT_ID')
           && env('OAUTH_CLIENT_SECRET')
           && (config('services.cas', []) !== []);
  }

  /**
   * Register any others events for your application.
   *
   * @return void
   */
  public function boot()
  {
    $providerName = env('OAUTH_PROVIDER');
    $clientId = env('OAUTH_CLIENT_ID');
    $clientSecret = env('OAUTH_CLIENT_SECRET');
    $redirect = url('/oauth/callback');

    if (!$this->requirements()) {
      return;
    }

    config()->set('services.' . $providerName, [
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
      'redirect' => $redirect
    ]);

    Event::listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, "\SocialiteProviders\\" . ucfirst($providerName) . "\\" . ucfirst($providerName) . "ExtendSocialite@handle");
  }

  /**
   * Register any application services.
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
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cas-oauth');
  }
}