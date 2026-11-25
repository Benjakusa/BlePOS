<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Notification stub for industry module alerts (gym expiry, agrovet expiry/overdue,
 * fleet document expiry, garage estimate reminders).
 *
 * Outbound SMS/WhatsApp/email campaign provider is not yet configured (see PROGRESS_LOG L-0129);
 * this stub logs structured alert events cleanly so modules can trigger alerts without inventing
 * a parallel notification provider.
 */
class ModuleAlertService
{
    /**
     * Dispatch a gated module alert event.
     *
     * @param  array<string, mixed>  $context
     * @return array{dispatched: bool, channel: string, module: string, event: string, context: array<string, mixed>}
     */
    public function send(string $module, string $event, int $tenantId, ?int $shopId, array $context = []): array
    {
        Log::info("ModuleAlert [{$module}:{$event}]", [
            'module' => $module,
            'event' => $event,
            'tenant_id' => $tenantId,
            'shop_id' => $shopId,
            'context' => $context,
        ]);

        return [
            'dispatched' => true,
            'channel' => 'stub_log',
            'module' => $module,
            'event' => $event,
            'context' => $context,
        ];
    }
}
