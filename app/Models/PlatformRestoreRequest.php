<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformRestoreRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
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
     * @return BelongsTo<PlatformBackupRun, $this>
     */
    public function backupRun(): BelongsTo
    {
        return $this->belongsTo(PlatformBackupRun::class, 'platform_backup_run_id');
    }

    /**
     * @return BelongsTo<PlatformUser, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'requested_by');
    }

    /**
     * @return BelongsTo<PlatformUser, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'approved_by');
    }
}
