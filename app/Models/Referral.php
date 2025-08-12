<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Referral extends Authenticatable
{
    protected $table = 'orders';

    public const STATUS_CREATE = 1;
    public const STATUS_DONE = 2;
    public const STATUS_EXPIRED = 100;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'requisite',
        'amount',
        'status_id',
    ];
}
