<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeSetting extends Model
{
    protected $table = 'exchange_settings';
    protected $fillable = ['key', 'value'];
    public $timestamps = true;
}
