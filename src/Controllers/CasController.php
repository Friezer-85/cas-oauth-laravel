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
      return response('This service isn\'t authorized to use the CAS.', 400);
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
   * Provides the function for the `/samlValidate` endpoint.
   *
   * @param bool $attributes
   *
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   * @return Response
   */
  public function samlValidate($attributes = true): Response
  {
	  function ticket_xml()
    {
	    $dom = new \DOMDocument();
	    $dom->loadXML(request()->getContent());
	    foreach ((new \DOMXpath($dom))->query('//SOAP-ENV:Body')->item(0)->childNodes as $node) {
	      if ($node->nodeName === 'samlp:Request') {
			    $ticket = trim($node->nodeValue);
			    break;
		    }
	    }
	
	    return $ticket;
	  }
    
    $ticket = !$attributes ? request()->get('ticket') : ticket_xml();
    $service = request()->input('service') ?? request()->input('target') ?? request()->input('TARGET');
    $decoded = explode('|', base64_decode(str_replace('ST-', '', $ticket)));

    try {
      if (!$ticket) {
        throw new \Exception('INVALID_REQUEST: Ticket not provided.');
      }
		
	  if (!$service) {
		 throw new \Exception('INVALID_REQUEST: Service not provided.');
	  }

      if (!Cache::get("cas-oauth.cas.tickets.{$decoded[2]}")) {
        throw new \Exception("INVALID_TICKET: Ticket {$ticket} not recognized.");
      }

      if ($decoded[1] !== $service) {
        throw new \Exception('INVALID_SERVICE: Service does not match ticket.');
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

    $user = Cache::get("cas-oauth.cas.users." . env('OAUTH_PROVIDER') . ".{$decoded[0]}");
	  if (!isset($response)) {
      $response = [
        'authenticationSuccess' => [
          'user' => $decoded[0],
        ]
      ];
		
      if ($attributes) {
	      $response['authenticationSuccess']['attributes'] = $user->attributes;
      }
	  }
	  
    if (isset($user->attributes['name'])) {
      [
        $first,
        $last
      ] = explode(' ', $user->attributes['name']);
      $user->attributes['first_name'] = $first;
      $user->attributes['last_name'] = $last;
      $user->attributes['id'] = strtoupper(substr($first, 0, 1) . strtr($last, 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));
    }

	  Cache::delete("cas-oauth.cas.tickets.{$decoded[2]}");
    Cache::delete("cas-oauth.cas.users." . env('OAUTH_PROVIDER') . ".{$decoded[0]}");
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
