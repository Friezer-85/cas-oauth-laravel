<?php

namespace Micorksen\CasOauth;

use Illuminate\Support\ServiceProvider as IServiceProvider;

class ServiceProvider extends IServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
  */
  public function register()
  {
    $this->loadRoutesFrom(__DIR__ . '/../resources/routes.php');
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cas-oauth');
  }
}