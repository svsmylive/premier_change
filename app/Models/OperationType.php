<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationType extends Model
{
    protected $table = 'operations_types';

    protected $fillable = [
        'name',
        'code',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(CashDeskMovement::class, 'operation_type_id');
    }
}
