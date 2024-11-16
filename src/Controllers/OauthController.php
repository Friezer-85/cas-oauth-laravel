<?php

namespace Friezer\CasOauth\Controllers;

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
        $parameters = [];
        foreach (explode(',', env('OAUTH_PARAMS', '')) as $parameter) {
            if (!$parameter) {
                continue;
            }
            
            [$key, $value] = explode('=', $parameter);
            $parameters[$key] = $value;
        }
        
        session(['cas-oauth.cas.service' => request()->get('service')]);
        return Socialite::driver(env('OAUTH_PROVIDER'))
            ->setScopes(explode(',', env('OAUTH_SCOPES', 'openid,profile,email')))
            ->with($parameters)
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
            session(['cas-oauth.cas.user' => Socialite::driver(env('OAUTH_PROVIDER'))->user()]);
            return redirect()->route('cas-oauth.cas.login', ['service' => session('cas-oauth.cas.service')]);
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            return redirect()->route('cas-oauth.oauth.login', ['service' => session('cas-oauth.cas.service')]);
        }
    }
}
