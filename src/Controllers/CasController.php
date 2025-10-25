<?php

namespace Friezer\CasOauth\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Friezer\CasOauth\Models\Ticket;
use Friezer\CasOauth\Models\Service;

class CasController extends Controller
{
    public function login(): Response|RedirectResponse
    {
        $user = session('cas-oauth.cas.user');
        $serviceUrl = request()->get('service', '');
        $service = new Service($serviceUrl);

        if (!$service->validate()) {
            return response('INVALID_SERVICE', 400)
                ->header('Content-Type', 'text/plain');
        }

        if (!$user) {
            return redirect()
                ->route('cas-oauth.oauth.login', ['service' => $service->url]);
        }

        session()->forget([
            'cas-oauth.cas.service',
            'cas-oauth.cas.user'
        ]);

        $ticketId = Ticket::generate($service->url, $user, request()->boolean('renew'));

        return $service->redirect($ticketId);
    }

    public function serviceValidate(): Response
    {
        return $this->validateTicket();
    }

    public function samlValidate(): Response
    {
        return $this->validateTicket();
    }

    private function validateTicket(): Response
    {
        $ticketParam = request()->get('ticket', '');
        $serviceUrl = request()->input('service') 
            ?? request()->input('target') 
            ?? request()->input('TARGET', '');

        if (empty($ticketParam) || empty($serviceUrl)) {
            return $this->errorResponse('INVALID_REQUEST', 'Missing ticket or service parameter');
        }

        $ticket = Ticket::findByTicket($ticketParam);

        if (!$ticket) {
            return $this->errorResponse('INVALID_TICKET', "Ticket {$ticketParam} not recognized");
        }

        $validationResult = $ticket->validate($serviceUrl, request()->boolean('renew'));

        if (is_string($validationResult)) {
            return $this->errorResponse($validationResult, "Ticket {$ticketParam} not recognized");
        }

        $casProperty = config('cas-oauth.cas_property', 'id');
        $userData = $validationResult->user;

        return $this->successResponse($userData[$casProperty] ?? $userData['id'], $userData);
    }

    private function successResponse(string $user, array $attributes): Response
    {
        $response = [
            'authenticationSuccess' => [
                'user' => $user,
                'attributes' => $attributes,
            ],
        ];

        return response()
            ->view('cas-oauth::ticket', $response)
            ->header('Content-Type', 'application/xml');
    }

    private function errorResponse(string $code, string $description = ''): Response
    {
        $response = [
            'authenticationFailure' => [
                'code' => $code,
                'description' => $description ?: "Ticket validation failed with code: {$code}",
            ],
        ];

        return response()
            ->view('cas-oauth::ticket', $response)
            ->header('Content-Type', 'application/xml');
    }
}