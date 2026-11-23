<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformHealthProbe extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latency_ms' => 'decimal:2',
            'details' => 'array',
            'probed_at' => 'datetime',
        ];
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }
}
