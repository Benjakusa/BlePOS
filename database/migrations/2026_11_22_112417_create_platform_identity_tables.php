<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status', 24)->default('active')->index();
            $table->text('totp_secret')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->json('recovery_code_hashes')->nullable();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('must_reset_password')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('platform_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description');
            $table->boolean('is_builtin')->default(false);
            $table->timestamps();
        });

        Schema::create('platform_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('area', 80);
            $table->string('action', 80);
            $table->text('description');
            $table->timestamps();
            $table->index(['area', 'action']);
        });

        Schema::create('platform_role_user', function (Blueprint $table): void {
            $table->foreignId('platform_role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['platform_role_id', 'platform_user_id']);
        });

        Schema::create('platform_permission_role', function (Blueprint $table): void {
            $table->foreignId('platform_permission_id')->constrained('platform_permissions')->cascadeOnDelete();
            $table->foreignId('platform_role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->primary(['platform_permission_id', 'platform_role_id']);
        });

        Schema::create('platform_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->string('session_hash', 64)->unique();
            $table->text('session_identifier');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
            $table->index(['platform_user_id', 'revoked_at']);
        });

        Schema::create('platform_security_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('event_type', 120)->index();
            $table->string('severity', 24)->default('info')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('platform_audit_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('action', 160)->index();
            $table->string('target_type', 120)->nullable();
            $table->string('target_id', 120)->nullable();
            $table->json('details')->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('entry_hash', 64)->unique();
            $table->timestamp('occurred_at')->index();
            $table->index(['target_type', 'target_id']);
        });

        $this->createAppendOnlyAuditTriggers();

        Schema::create('platform_approvals', function (Blueprint $table): void {
            $table->id();
            $table->string('action_type', 120)->index();
            $table->string('target_type', 120);
            $table->string('target_id', 120);
            $table->foreignId('requested_by')->constrained('platform_users');
            $table->foreignId('approved_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->text('reason');
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropAppendOnlyAuditTriggers();
        Schema::dropIfExists('platform_approvals');
        Schema::dropIfExists('platform_audit_log');
        Schema::dropIfExists('platform_security_events');
        Schema::dropIfExists('platform_sessions');
        Schema::dropIfExists('platform_permission_role');
        Schema::dropIfExists('platform_role_user');
        Schema::dropIfExists('platform_permissions');
        Schema::dropIfExists('platform_roles');
        Schema::dropIfExists('platform_users');
    }

    private function createAppendOnlyAuditTriggers(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => $this->createSqliteAuditTriggers(),
            'mysql' => $this->createMysqlAuditTriggers(),
            'pgsql' => $this->createPostgresAuditTriggers(),
            default => null,
        };
    }

    private function createSqliteAuditTriggers(): void
    {
        DB::unprepared("CREATE TRIGGER platform_audit_log_no_update BEFORE UPDATE ON platform_audit_log BEGIN SELECT RAISE(ABORT, 'platform audit log is append-only'); END");
        DB::unprepared("CREATE TRIGGER platform_audit_log_no_delete BEFORE DELETE ON platform_audit_log BEGIN SELECT RAISE(ABORT, 'platform audit log is append-only'); END");
    }

    private function createMysqlAuditTriggers(): void
    {
        DB::unprepared("CREATE TRIGGER platform_audit_log_no_update BEFORE UPDATE ON platform_audit_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'platform audit log is append-only'");
        DB::unprepared("CREATE TRIGGER platform_audit_log_no_delete BEFORE DELETE ON platform_audit_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'platform audit log is append-only'");
    }

    private function createPostgresAuditTriggers(): void
    {
        DB::unprepared("CREATE FUNCTION reject_platform_audit_mutation() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'platform audit log is append-only'; END; $$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER platform_audit_log_no_update BEFORE UPDATE OR DELETE ON platform_audit_log FOR EACH ROW EXECUTE FUNCTION reject_platform_audit_mutation()');
    }

    private function dropAppendOnlyAuditTriggers(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => $this->dropSqliteAuditTriggers(),
            'mysql' => $this->dropMysqlAuditTriggers(),
            'pgsql' => $this->dropPostgresAuditTriggers(),
            default => null,
        };
    }

    private function dropSqliteAuditTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS platform_audit_log_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS platform_audit_log_no_delete');
    }

    private function dropMysqlAuditTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS platform_audit_log_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS platform_audit_log_no_delete');
    }

    private function dropPostgresAuditTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS platform_audit_log_no_update ON platform_audit_log');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_platform_audit_mutation()');
    }
};
