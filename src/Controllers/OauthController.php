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
    $params = [];
    foreach (explode(',', env('OAUTH_PARAMS', '')) as $s) {
      [
        $key,
        $value
      ] = explode('=', $s);
      $params[$key] = $value;
    }
    
    session()->put('cas-oauth.cas.service', request()->get('service'));
    return Socialite::driver(env('OAUTH_PROVIDER'))
      ->setScopes(explode(',', env('OAUTH_SCOPES', 'openid,profile,email')))
      ->with($params)
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
      session()->put('cas-oauth.cas.user', Socialite::driver(env('OAUTH_PROVIDER'))->user());
      return redirect()->route('cas-oauth.cas.login', ['service' => session('cas-oauth.cas.service')]);
    } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
      return redirect()->route('cas-oauth.oauth.login', ['service' => session('cas-oauth.cas.service')]);
    }
  }
}
