<?php

namespace App\Console\Commands;

use App\Models\MpesaTransaction;
use App\Models\PlatformUser;
use App\Notifications\WorkspaceAlert;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('mpesa:report-unsettled-sales')]
#[Description('Report successful M-Pesa POS payments without a completed sale.')]
class ReportUnsettledMpesaSalesCommand extends Command
{
    public function handle(): int
    {
        $transactions = MpesaTransaction::query()
            ->whereNotNull('sale_id')
            ->where(function ($query): void {
                $query->where('status', 'refund_required')
                    ->orWhere(function ($confirmed): void {
                        $confirmed->where('status', 'confirmed')
                            ->where(fn ($saleQuery) => $saleQuery
                                ->whereHas('sale', fn ($sale) => $sale->where('status', '!=', 'completed'))
                                ->orWhereDoesntHave('sale'));
                    });
            })
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            $this->components->info('No paid M-Pesa POS transactions are missing a completed sale.');

            return self::SUCCESS;
        }

        $this->components->error("{$transactions->count()} paid M-Pesa POS transaction(s) need reconciliation.");
        $this->table(
            ['Transaction', 'Tenant', 'Sale', 'Amount', 'Receipt', 'Status'],
            $transactions->map(fn (MpesaTransaction $transaction): array => [
                $transaction->id,
                $transaction->tenant_id,
                $transaction->sale_id,
                $transaction->amount,
                $transaction->provider_receipt ?? 'pending receipt',
                $transaction->status,
            ])->all()
        );

        if (Cache::add('mpesa-unsettled-sales-alert-sent', true, now()->addHour())) {
            PlatformUser::query()->where('status', 'active')->get()
                ->filter(fn (PlatformUser $user): bool => $user->hasPermission('platform.billing.subscriptions.manage'))
                ->each(fn (PlatformUser $user) => $user->notify(new WorkspaceAlert([
                    'title' => 'M-Pesa POS reconciliation required',
                    'message' => "{$transactions->count()} successful M-Pesa POS transaction(s) do not have a completed sale. Review the unsettled payment report.",
                    'url' => route('platform.mpesa.refunds.index'),
                    'kind' => 'payment',
                ])));
        }

        return self::FAILURE;
    }
}
