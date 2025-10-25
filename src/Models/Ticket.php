<?php
namespace Friezer\CasOauth\Models;

use Illuminate\Database\Eloquent\Model;
use SocialiteProviders\Manager\OAuth2\User;
use Carbon\Carbon;

class Ticket extends Model
{
    protected $table = 'tickets';
    public $incrementing = false;
    public $timestamps = false;
    
    protected $casts = [
        'user' => 'json',
        'renew' => 'boolean',
        'createdAt' => 'datetime',
    ];

    protected $fillable = [
        'id',
        'service',
        'user',
        'renew',
        'createdAt',
    ];

    private const MAXIMUM_INTERVAL_SECONDS = 10;
    private const TICKET_ID_LENGTH = 32;

    private static function generateRandomString(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $max)];
        }
        
        return $result;
    }

    private static function generateUniqueId(): string
    {
        $maxAttempts = 10;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $key = self::generateRandomString(self::TICKET_ID_LENGTH);
            
            if (!self::where('id', $key)->exists()) {
                return $key;
            }
        }
        
        throw new \RuntimeException('Failed to generate unique ticket ID after ' . $maxAttempts . ' attempts');
    }

    private static function getTicketPrefix(): string
    {
        $domain = str_replace(['http:', 'https:', '/'], '', url('/'));
        return "ST-{$domain}-";
    }

    private static function convertIdToTicket(string $id): string
    {
        return self::getTicketPrefix() . $id;
    }

    public static function convertTicketToId(string $ticket): ?string
    {
        $prefix = self::getTicketPrefix();
        
        if (!str_starts_with($ticket, $prefix)) {
            return null;
        }
        
        return substr($ticket, strlen($prefix));
    }

    public static function generate(string $service, User $user, bool $renew = false): string
    {
        $ticket = new self();
        $ticket->id = self::generateUniqueId();
        $ticket->service = $service;
        $ticket->user = $user->user;
        $ticket->renew = $renew;
        $ticket->createdAt = Carbon::now();
        $ticket->save();

        return self::convertIdToTicket($ticket->id);
    }

    public function validate(string $service, bool $renew): self|string
    {
        if (empty($service)) {
            return 'INVALID_REQUEST';
        }

        if (empty($this->id)) {
            return 'INVALID_TICKET_SPEC';
        }

        $expirationThreshold = Carbon::now()->subSeconds(self::MAXIMUM_INTERVAL_SECONDS);
        
        if ($this->createdAt < $expirationThreshold) {
            $this->delete();
            return 'INVALID_TICKET';
        }

        if ($this->service !== $service) {
            return 'INVALID_SERVICE';
        }

        if ($renew && !$this->renew) {
            return 'INVALID_RENEW';
        }

        $this->delete();
        
        return $this;
    }

    public static function findByTicket(string $ticket): ?self
    {
        $id = self::convertTicketToId($ticket);
        
        if ($id === null) {
            return null;
        }
        
        return self::find($id);
    }
}