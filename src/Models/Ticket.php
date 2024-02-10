<?php

namespace Micorksen\CasOauth\Models;

use Illuminate\Database\Eloquent\Model;
use SocialiteProviders\Manager\OAuth2\User;
use Carbon\Carbon;

class Ticket extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'tickets';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     * 
     * @var array
     */
    protected $casts = [
        'user' => 'json',
        'renew' => 'boolean',
    ];

    /**
     * Indicates the maximum interval for a ticket.
     * 
     * @var string
     */
    public $maximumInterval;

    /**
     * The constructor for the model.
     * @param array $attributes
     * 
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->maximumInterval = '10 seconds';
    }

    /**
     * Generates a random string.
     * 
     * @var int $length
     * @var string $characters
     * 
     * @return string
     */
    private function random_string(int $length, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $result = '';
        $max = mb_strlen($characters, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $result .= $characters[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * Generates an ID for a ticket.
     * 
     * @return string
     */
    private function get_id(): string
    {
        do {
            $key = $this->random_string(32);
            if (!self::where('id', '=', $key)->exists()) {
                return $key;
            }
        } while(true);
    }

    /**
     * Returns the ticket prefix.
     * 
     * @return string
     */
    private static function ticket_prefix(): string
    {
        return 'ST-' . str_replace(['http:', 'https:', '/'], '', url('/')) . '-';
    }

    /**
     * Converts an ID to a ticket ID.
     * @param string $id
     * 
     * @return string
     */
    private function convert_id(string $id): string
    {
        return $this->ticket_prefix() . $id;
    }

    /**
     * Converts a ticket ID to an ID.
     * @param string $id
     * 
     * @return string
     */
    public static function convert_ticket_id(string $id): string
    {
        $prefix = self::ticket_prefix();
        return substr($id, 0, strlen($prefix)) !== $prefix ? "" : substr($id, strlen($prefix));
    }

    /**
     * Generates a ticket.
     * @param string $service
     * @param User $user
     * @param bool $renew
     * 
     * @return string
     */
    public function generate(string $service, User $user, bool $renew = false): string
    {
        $ticket = new self;
        $ticket->id = $this->get_id();
        $ticket->service = $service;
        $ticket->user = $user->user;
        $ticket->renew = $renew;
        $ticket->createdAt = Carbon::now();
        $ticket->save();

        return $this->convert_id($ticket->id);
    }

    /**
     * Validates a ticket.
     * @param string $service
     * @param bool $renew
     * 
     * @return Ticket|string
     */
    public function validate(string $service, bool $renew): Ticket | string
    {
        if (!$this || !$service) {
            return 'INVALID_REQUEST';
        }

        $ticket = $this->id;
        if (!$ticket) {
            return 'INVALID_TICKET_SPEC';
        }

        $ticket = $this->where('id', '=', $ticket)
            ->where('createdAt', '>', Carbon::parse("-{$this->maximumInterval}"))
            ->first();

        if (is_null($ticket)) {
            return 'INVALID_TICKET';
        }

        $this->delete();
        if ($ticket->service != $service) {
            return 'INVALID_SERVICE';
        }

        if ($renew && !$ticket->renew) {
            return 'INVALID_RENEW';
        }

        return $ticket;
    }
}