<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgrovetCustomer extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'phone', 'village',
        'credit_limit', 'credit_balance', 'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(AgrovetSale::class, 'customer_id');
    }

    public function creditLedger(): HasMany
    {
        return $this->hasMany(AgrovetCreditLedger::class, 'customer_id');
    }
}
