<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformPayment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlatformInvoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<PlatformRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PlatformRefund::class);
    }

    /**
     * Get the total amount already refunded for this payment.
     */
    public function refundedAmount(): string
    {
        $sum = '0.00';
        foreach ($this->refunds()->where('status', 'completed')->get() as $refund) {
            $sum = bcadd($sum, (string) $refund->amount, 2);
        }

        return $sum;
    }

    /**
     * Get remaining refundable amount.
     */
    public function refundableBalance(): string
    {
        $refunded = $this->refundedAmount();

        return bcsub((string) $this->amount, $refunded, 2);
    }
}
