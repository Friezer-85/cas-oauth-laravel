<?php

namespace Micorksen\CasOauth\Controllers;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OauthController extends Controller
{
  /**
   * Provides the function for the `/login` endpoint.
   *
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   * @return RedirectResponse
   */
  public function login(): RedirectResponse
  {
    session()->put('cas.service', request()->get('service'));
    return Socialite::driver(env('OAUTH_PROVIDER'))
      ->scopes(['identify'])
      ->redirect();
  }

  /**
   * Provides the function for the `/callback` endpoint.
   *
   * @return RedirectResponse
   */
  public function callback(): RedirectResponse
  {
    try {
      session()->put('cas.user', Socialite::driver(env('OAUTH_PROVIDER'))->user());
      return redirect()->route('cas.login', ['service' => session('cas.service')]);
    } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
      return redirect()->route('oauth.login', ['service' => session('cas.service')]);
    }
  }
}