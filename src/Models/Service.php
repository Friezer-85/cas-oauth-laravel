<?php
namespace Friezer\CasOauth\Models;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class Service
{
    private Collection $services;
    public string $url;

    public function __construct(string $url)
    {
        $this->services = collect(config('services.cas', []));
        $this->url = $url;
    }

    public function validate(): bool
    {
        if (empty($this->url)) {
            return false;
        }

        return $this->services->contains(function (string $pattern) {
            $result = @preg_match("#{$pattern}#", $this->url);
            
            if ($result === false) {
                \Log::warning("Invalid CAS service regex pattern: {$pattern}");
                return false;
            }
            
            return $result === 1;
        });
    }

    public function redirect(string $ticket): RedirectResponse
    {
        if (empty($this->url)) {
            throw new \InvalidArgumentException('Cannot redirect to empty service URL');
        }

        $separator = str_contains($this->url, '?') ? '&' : '?';
        $redirectUrl = $this->url . $separator . 'ticket=' . urlencode($ticket);
        
        return new RedirectResponse($redirectUrl);
    }
}