<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformInvoice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<PlatformInvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PlatformInvoiceItem::class);
    }

    /**
     * @return HasMany<PlatformPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    /**
     * @return HasMany<PlatformRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PlatformRefund::class);
    }

    /**
     * @return HasMany<PlatformDunningEvent, $this>
     */
    public function dunningEvents(): HasMany
    {
        return $this->hasMany(PlatformDunningEvent::class);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' || bccomp((string) $this->amount_due, '0.00', 2) <= 0;
    }

    /**
     * Check if invoice is overdue as of today.
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid() || in_array($this->status, ['voided', 'cancelled'], true)) {
            return false;
        }

        return $this->due_date < now()->startOfDay();
    }
}
