<?php

namespace Micorksen\CasOauth\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CasController extends Controller
{
  /**
   * Provides the function for the `/login` endpoint.
   *
   * @return Response | RedirectResponse
   * @throws NotFoundExceptionInterface
   * @throws ContainerExceptionInterface
   */
  public function login(): Response | RedirectResponse {
    $service = request()->get('service');
    $matches = false;
    $user = session('cas-oauth.cas.user');

    foreach (config('services.cas') as $regex) {
      if (preg_match($regex, $service)) {
        $matches = true;
        break;
      }
    }

    if (!$service || !$matches) {
      return response('The service isn\'t authorized to use the CAS.', 400);
    }

    if (!$user) {
      return redirect()->route('cas-oauth.oauth.login', ['service' => $service]);
    }

    $now = Carbon::now()->timestamp;
    $ticket = 'ST-' . base64_encode("{$user[env('CAS_ID_PROP', 'id')]}|$service|$now");

    Cache::add("cas-oauth.cas.tickets.$now", $ticket);
    Cache::add("cas-oauth.cas.users." . env('OAUTH_PROVIDER') . ".{$user[env('CAS_ID_PROP', 'id')]}", $user);
    session()->forget([
      'cas-oauth.cas.service',
      'cas-oauth.cas.user'
    ]);

    return redirect($service . '?ticket=' . $ticket);
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
    $ticket = request()->input('ticket');
    $service = request()->input('service');
    $decoded = explode('|', base64_decode(str_replace('ST-', '', $ticket)));

    try {
      if (!$ticket) {
        throw new \Exception('NO_TICKET: Ticket not provided.');
      }

      if (!Cache::get("cas-oauth.cas.tickets.{$decoded[2]}")) {
        throw new \Exception("INVALID_TICKET: Ticket {$ticket} not recognized.");
      }

      if ($decoded[1] !== $service) {
        throw new \Exception('MISMATCH_SERVICE: Service does not match ticket.');
      }
    } catch (\Exception $e) {
      [
        $code,
        $description
      ] = explode(': ', $e->getMessage());

      $response = [
        'authenticationFailure' => [
          'code' => $code,
          'description' => $description
        ]
      ];
    }

    $user = Cache::get("cas-oauth.cas.users.google.{$decoded[0]}");
    if (isset($user->attributes['name'])) {
      [
        $first,
        $last
      ] = explode(' ', $user->attributes['name']);
      $user->attributes['first_name'] = $first;
      $user->attributes['last_name'] = $last;
    }
    
    if (!isset($response)) {
      $response = [
        'authenticationSuccess' => [
          'user' => $decoded[0],
          'attributes' => $user->attributes
        ]
      ];
    }

    Cache::delete("cas-oauth.cas.tickets.{$decoded[2]}");
    return response()
      ->view('cas-oauth::ticket', $response)
      ->header('Content-Type', 'application/xml');
  }
}
