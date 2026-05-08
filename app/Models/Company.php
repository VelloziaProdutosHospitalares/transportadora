<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'legal_name',
        'trade_name',
        'cnpj',
        'state_registration',
        'phone',
        'email',
        'postal_code',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'logo_path',
    ];

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
}
