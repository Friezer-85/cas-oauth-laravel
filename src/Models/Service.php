<?php

namespace Friezer-85\CasOauth\Models;

use Illuminate\Http\RedirectResponse;

class Service
{
    /**
     * The services of the CAS server.
     * 
     * @var array
     */
    private $services;

    /**
     * The URL of the service associated with the model.
     * 
     * @var string
     */
    public $url;

    /**
     * Collects all the services.
     * @param string $url
     * 
     * @return void
     */
    public function __construct(string $url)
    {
        $this->services = collect(config('services.cas'));
        $this->url = $url;
    }

    /**
     * Validates a service.
     * 
     * @return bool
     */
    public function validate(): bool
    {
        return $this->services->first(function ($value, $key) {
            return preg_match("#{$value}#", $this->url);
        }, false);
    }

    /**
     * Redirects to a service.
     * @param string $ticket
     * 
     * @return RedirectResponse
     */
    public function redirect(string $ticket): RedirectResponse
    {
        return new RedirectResponse($this->url . (str_contains($this->url, '?') ? '&' : '?') . 'ticket=' . urlencode($ticket));
    }
}
