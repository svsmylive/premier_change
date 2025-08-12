<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Order extends Authenticatable
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
        'ref_user_id',
        'status_id',
        'valute_from',
        'valute_to',
        'sum_from',
        'sum_to',
        'course_from',
        'course_to',
        'city',
    ];
}
