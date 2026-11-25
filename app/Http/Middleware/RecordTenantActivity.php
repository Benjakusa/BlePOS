<?php

namespace App\Http\Middleware;

use App\Models\Sale;
use App\Models\TenantActivityLog;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordTenantActivity
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $response->getStatusCode() >= 400
            || ! $request->user()) {
            return $response;
        }

        $tenant = TenantContext::getTenant();
        if (! $tenant) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        [$action, $subjectType, $subjectId, $subjectLabel] = $this->describe($request, $response, $routeName);

        try {
            TenantActivityLog::query()->create([
                'tenant_id' => $tenant->id,
                'shop_id' => ShopContext::getShopId(),
                'user_id' => $request->user()->id,
                'action' => $action,
                'route_name' => $routeName,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'subject_label' => $subjectLabel,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    /** @return array{string, ?string, ?string, ?string} */
    private function describe(Request $request, Response $response, ?string $routeName): array
    {
        if ($routeName === 'tenant.pos.process' && $response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (is_array($payload) && isset($payload['sale_id'])) {
                $sale = Sale::query()->find($payload['sale_id']);
                if ($sale) {
                    return ["Completed sale {$sale->sale_number}", Sale::class, (string) $sale->id, "Sale {$sale->sale_number}"];
                }
            }
        }

        foreach ((array) ($request->route()?->parameters() ?? []) as $parameter) {
            if ($parameter instanceof Model) {
                return [$this->actionLabel($routeName, $request->method()), $parameter::class, (string) $parameter->getKey(), $this->subjectLabel($parameter)];
            }
        }

        return [$this->actionLabel($routeName, $request->method()), null, null, null];
    }

    private function subjectLabel(Model $subject): string
    {
        foreach (['sale_number', 'name', 'title', 'number', 'sku'] as $attribute) {
            $value = $subject->getAttribute($attribute);
            if (is_scalar($value) && (string) $value !== '') {
                return Str::limit((string) $value, 180)." (#{$subject->getKey()})";
            }
        }

        return '#'.$subject->getKey();
    }

    private function actionLabel(?string $routeName, string $method): string
    {
        $known = [
            'tenant.pos.process' => 'Completed a sale',
            'tenant.pos.void' => 'Voided a sale',
            'tenant.shifts.store' => 'Opened a cashier shift',
            'tenant.shifts.close' => 'Closed a cashier shift',
            'tenant.inventory.receive' => 'Received inventory',
            'tenant.inventory.adjust' => 'Adjusted inventory',
            'tenant.notifications.read' => 'Marked a notification as read',
            'tenant.notifications.read-all' => 'Marked notifications as read',
        ];
        if ($routeName && isset($known[$routeName])) {
            return $known[$routeName];
        }

        $parts = explode('.', (string) $routeName);
        $operation = array_pop($parts) ?: 'action';
        $resource = Str::headline(array_pop($parts) ?: 'workspace');
        $verb = match ($method) {
            'POST' => in_array($operation, ['store', 'create'], true) ? 'Created' : 'Performed',
            'PUT', 'PATCH' => 'Updated',
            'DELETE' => 'Deleted',
            default => 'Changed',
        };

        return "{$verb} {$resource}";
    }
}
