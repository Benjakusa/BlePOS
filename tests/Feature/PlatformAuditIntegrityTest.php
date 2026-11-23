<?php

namespace Tests\Feature;

use App\Services\PlatformAuditRecorder;
use App\Services\PlatformAuditVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PlatformAuditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_entries_form_a_verifiable_hash_chain(): void
    {
        $recorder = app(PlatformAuditRecorder::class);
        $recorder->record(null, 'system.started', details: ['environment' => 'test']);
        $recorder->record(null, 'system.checked', details: ['count' => 1]);

        $result = app(PlatformAuditVerifier::class)->verify();

        $this->assertTrue($result['intact']);
        $this->assertSame(2, $result['entries_checked']);
        $this->assertNull($result['broken_entry_id']);
    }

    public function test_audit_recording_fails_closed_when_the_chain_lock_row_is_missing(): void
    {
        DB::table('platform_audit_chain_locks')->delete();
        $this->expectException(RuntimeException::class);

        app(PlatformAuditRecorder::class)->record(null, 'system.started');
    }

    public function test_database_rejects_audit_updates(): void
    {
        $entry = app(PlatformAuditRecorder::class)->record(null, 'system.started');
        $this->expectException(QueryException::class);

        DB::table('platform_audit_log')->where('id', $entry->id)->update(['action' => 'tampered']);
    }

    public function test_database_rejects_audit_deletes(): void
    {
        $entry = app(PlatformAuditRecorder::class)->record(null, 'system.started');
        $this->expectException(QueryException::class);

        DB::table('platform_audit_log')->where('id', $entry->id)->delete();
    }

    public function test_verifier_detects_a_tampered_entry_after_database_protection_is_removed(): void
    {
        $entry = app(PlatformAuditRecorder::class)->record(null, 'system.started', details: ['original' => true]);
        DB::unprepared('DROP TRIGGER platform_audit_log_no_update');
        DB::table('platform_audit_log')->where('id', $entry->id)->update(['details' => '{"original":false}']);

        $result = app(PlatformAuditVerifier::class)->verify();

        $this->assertFalse($result['intact']);
        $this->assertSame($entry->id, $result['broken_entry_id']);
    }
}
