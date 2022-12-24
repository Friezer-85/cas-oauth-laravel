<?php

namespace Micorksen\CasOauth;

use Illuminate\Support\ServiceProvider as IServiceProvider;
use Illuminate\Support\Facades\Event;

class ServiceProvider extends IServiceProvider
{
  /**
   * The event handler mappings for the application.
   *
   * @var array
   */
  protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
      'SocialiteProviders\Discord\\DiscordExtendSocialite@handle'
    ]
  ];

  /**
   * Register any others events for your application.
   *
   * @return void
   */
  public function boot()
  {
    $providerName = ucfirst(env('OAUTH_PROVIDER'));
    config()->set('services.' . env('OAUTH_PROVIDER'), [
      'client_id' => env('OAUTH_CLIENT_ID'),
      'client_secret' => env('OAUTH_CLIENT_SECRET'),
      'redirect' => url('/oauth/callback')
    ]);

    Event::listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, "\SocialiteProviders\{$providerName}\{$providerName}ExtendSocialite@handle");
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->register('SocialiteProviders\Manager\ServiceProvider');
    $this->loadRoutesFrom(__DIR__ . '/../resources/routes.php');
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cas-oauth');
  }
}