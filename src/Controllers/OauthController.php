<?php

namespace Friezer\CasOauth\Controllers;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class OauthController extends Controller
{
    public function login(): RedirectResponse
    {
        $provider = config('cas-oauth.provider');
        $scopes = $this->parseScopes(config('cas-oauth.scopes', 'openid,profile,email'));
        $parameters = $this->parseParameters(config('cas-oauth.params', ''));

        session(['cas-oauth.cas.service' => request()->get('service')]);

        return Socialite::driver($provider)
            ->setScopes($scopes)
            ->with($parameters)
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $provider = config('cas-oauth.provider');
            $user = Socialite::driver($provider)->user();
            
            session(['cas-oauth.cas.user' => $user]);
            
            return redirect()->route(
                'cas-oauth.cas.login', 
                ['service' => session('cas-oauth.cas.service')]
            );
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            return redirect()->route(
                'cas-oauth.oauth.login', 
                ['service' => session('cas-oauth.cas.service')]
            );
        } catch (\Exception $e) {
            \Log::error('OAuth callback error: ' . $e->getMessage());
            
            return redirect()->route(
                'cas-oauth.oauth.login', 
                ['service' => session('cas-oauth.cas.service')]
            );
        }
    }

    private function parseScopes(string $scopes): array
    {
        return array_filter(
            array_map('trim', preg_split('/[,\s]+/', $scopes)),
            fn($scope) => !empty($scope)
        );
    }

    private function parseParameters(string $params): array
    {
        if (empty($params)) {
            return [];
        }

        $parameters = [];
        $pairs = array_filter(explode(',', $params));

        foreach ($pairs as $pair) {
            if (!str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $parameters[trim($key)] = trim($value);
        }

        return $parameters;
    }
}