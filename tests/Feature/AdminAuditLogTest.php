<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_peminjam_cannot_access_admin_audit_logs(): void
    {
        $peminjam = User::where('role', 'PEMINJAM')->first();
        $this->actingAs($peminjam);

        $response = $this->get(route('admin.audit-logs'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_audit_logs_page(): void
    {
        $admin = User::where('role', 'ADMIN')->first();
        $this->actingAs($admin);

        // Record a test audit log
        AuditLog::record('TEST_ACTION', 'User', (string)$admin->id, null, ['field' => 'value']);

        $response = $this->get(route('admin.audit-logs'));
        $response->assertStatus(200);
        $response->assertSee('Log Aktivitas');
        $response->assertSee('TEST_ACTION');
    }

    public function test_audit_logs_can_be_filtered(): void
    {
        $admin = User::where('role', 'ADMIN')->first();
        $this->actingAs($admin);

        AuditLog::record('SPECIAL_AUDIT_EVENT', 'SpecialEntity', '123', null, ['foo' => 'bar']);

        $response = $this->get(route('admin.audit-logs', ['action' => 'SPECIAL_AUDIT_EVENT']));
        $response->assertStatus(200);
        $response->assertSee('SPECIAL_AUDIT_EVENT');
    }
}
