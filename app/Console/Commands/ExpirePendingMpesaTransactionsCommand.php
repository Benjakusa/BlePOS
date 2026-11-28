<?php

namespace App\Console\Commands;

use App\Models\MpesaTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\WorkspaceAlert;
use App\Services\SaleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('mpesa:expire-pending-transactions')]
#[Description('Mark unconfirmed M-Pesa attempts as timed out and notify their cashiers.')]
class ExpirePendingMpesaTransactionsCommand extends Command
{
    public function handle(SaleService $sales): int
    {
        $expiredCount = 0;
        MpesaTransaction::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(10))
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$expiredCount, $sales): void {
                foreach ($transactions as $transaction) {
                    $sale = DB::transaction(function () use ($transaction, $sales) {
                        $locked = MpesaTransaction::query()->lockForUpdate()->find($transaction->id);
                        if (! $locked || $locked->status !== 'pending') {
                            return null;
                        }
                        $locked->forceFill(['status' => 'timed_out', 'result_code' => 'timeout'])->save();
                        if (! $locked->sale_id || MpesaTransaction::query()
                            ->where('sale_id', $locked->sale_id)
                            ->where('status', 'pending')
                            ->exists()) {
                            return ['sale' => null];
                        }

                        $sale = Sale::withoutGlobalScopes()->lockForUpdate()->find($locked->sale_id);
                        if ($sale) {
                            $sales->releasePendingMpesaStock($sale);
                        }

                        return ['sale' => $sale];
                    });
                    if ($sale === null) {
                        // The attempt may have been confirmed or failed while this job was scanning.
                        $timedOut = MpesaTransaction::query()->whereKey($transaction->id)->where('status', 'timed_out')->exists();
                        if (! $timedOut) {
                            continue;
                        }
                        $sale = null;
                    } else {
                        $sale = $sale['sale'];
                    }
                    if (! $transaction->sale_id) {
                        continue;
                    }
                    $cashier = $sale ? User::withoutGlobalScopes()->find($sale->user_id) : null;
                    if ($sale && $cashier) {
                        $cashier->notify(new WorkspaceAlert([
                            'title' => 'M-Pesa payment not confirmed',
                            'message' => "STK attempt for sale {$sale->sale_number} timed out. The checkout is still available; verify the customer's M-Pesa status before retrying.",
                            'url' => route('tenant.pos.checkout'),
                            'kind' => 'payment',
                        ]));
                    }
                    $expiredCount++;
                }
            });

        $this->components->info("Marked {$expiredCount} M-Pesa POS attempt(s) as timed out.");

        return self::SUCCESS;
    }
}
