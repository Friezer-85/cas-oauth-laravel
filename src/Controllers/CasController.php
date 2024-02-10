<?php

namespace Micorksen\CasOauth\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use Micorksen\CasOauth\Models\Ticket;
use Micorksen\CasOauth\Models\Service;

class CasController extends Controller
{
    /**
     * Provides the function for the `/login` endpoint.
     *
     * @return Response|RedirectResponse
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function login(): Response | RedirectResponse
    {
        $user = session('cas-oauth.cas.user');
        $service = new Service(request()->get('service', ''));

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

        $ticket = (new Ticket())
            ->generate(request()->get('service'), $user);

        return $service->redirect($ticket);
    }

    /**
     * Provides the function for the `/samlValidate` endpoint.
     *
     * @param bool $attributes
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return Response
     */
    public function samlValidate(bool $attributes = true): Response
    {
        $ticket = Ticket::find(Ticket::convert_ticket_id(request()->get('ticket'))) ?? new Ticket();
        $service = request()->input('service') ?? request()->input('target') ?? request()->input('TARGET', '');
        $valid = $ticket->validate($service, request()->boolean('renew'));

        if (is_object($valid)) {
            $response = [
                'authenticationSuccess' => [
                    'user' => $valid->user[env('CAS_PROPERTY', 'id')],
                    'attributes' => $valid->user,
                    'proxyGrantingTicket' => null,
                ],
            ];
        } else {
            $response = [
                'authenticationFailure' => [
                    'code' => $valid,
                    'description' => 'Ticket ' . request()->get('ticket') . ' not recognized.',
                ],
            ];
        }

        return response()
            ->view('cas-oauth::ticket', $response)
            ->header('Content-Type', 'application/xml');
    }
  
    /**
     * Provides the function for the `/serviceValidate` endpoint.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return Response
     */
    public function serviceValidate(): Response
    {
        return $this->samlValidate(false);
    }
}
