<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgrovetCreditLedger extends Model
{
    use BelongsToShop, BelongsToTenant;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $table = 'agrovet_credit_ledger';

    protected $fillable = [
        'tenant_id', 'shop_id', 'customer_id', 'type', 'amount',
        'reference', 'method', 'payment_meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_meta' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AgrovetCustomer::class, 'customer_id');
    }
}
