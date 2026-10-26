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

    public function test_loan_audit_accessible_by_pengelola_and_admin_forbidden_for_peminjam(): void
    {
        $peminjam = User::where('role', 'PEMINJAM')->first();
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $admin = User::where('role', 'ADMIN')->first();

        // 1. Peminjam ditolak (403)
        $this->actingAs($peminjam);
        $this->get(route('audit.loans'))->assertStatus(403);

        // 2. Pengelola BMN berhak mengakses (200)
        $this->actingAs($pengelola);
        $resPengelola = $this->get(route('audit.loans'));
        $resPengelola->assertStatus(200);
        $resPengelola->assertSee('Audit Aktivitas Peminjaman BMN');

        // 3. Admin berhak mengakses (200)
        $this->actingAs($admin);
        $resAdmin = $this->get(route('audit.loans'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Audit Aktivitas Peminjaman BMN');
    }

    public function test_loan_audit_only_shows_loan_activities_without_sensitive_data(): void
    {
        $admin = User::where('role', 'ADMIN')->first();
        $this->actingAs($admin);

        // Record loan activity
        AuditLog::record('TRANSITION_LOAN', 'LoanRequest', 'test-uuid-loan', ['status' => 'DRAFT'], ['status' => 'SUBMITTED', 'note' => 'Pengajuan resmi BMN']);

        // Record non-loan system activity (sensitive)
        AuditLog::record('AUTH_LOGIN_SENSITIVE', 'User', (string)$admin->id, null, ['ip' => '192.168.99.99', 'token' => 'secret_token_123']);

        $response = $this->get(route('audit.loans'));
        $response->assertStatus(200);

        // Memuat aktivitas peminjaman
        $response->assertSee('Pengajuan resmi BMN');

        // TIDAK memuat aktivitas teknis di luar peminjaman
        $response->assertDontSee('AUTH_LOGIN_SENSITIVE');
        $response->assertDontSee('secret_token_123');
    }

    public function test_loan_audit_export_works_for_xlsx_and_csv(): void
    {
        $admin = User::where('role', 'ADMIN')->first();
        $this->actingAs($admin);

        AuditLog::record('TRANSITION_LOAN', 'LoanRequest', 'test-uuid-loan-export', ['status' => 'DRAFT'], ['status' => 'SUBMITTED', 'note' => 'Catatan telaah pengajuan']);

        // 1. Ekspor XLSX
        $resXlsx = $this->get(route('audit.loans.export', ['filter_mode' => 'month', 'month' => date('n'), 'year' => date('Y'), 'format' => 'xlsx']));
        $resXlsx->assertStatus(200);
        $this->assertTrue(str_contains($resXlsx->headers->get('content-disposition'), '.xlsx'));

        // 2. Ekspor CSV
        $resCsv = $this->get(route('audit.loans.export', ['filter_mode' => 'year', 'year' => date('Y'), 'format' => 'csv']));
        $resCsv->assertStatus(200);
        $this->assertTrue(str_contains($resCsv->headers->get('content-disposition'), '.csv'));
        $csvContent = $resCsv->streamedContent();

        $this->assertStringContainsString('Catatan telaah pengajuan', $csvContent);
        // Pastikan kolom baru ada di header
        $this->assertStringContainsString('Nomor Dokumen Resmi', $csvContent);
        $this->assertStringContainsString('NIP Peminjam', $csvContent);
        $this->assertStringContainsString('Jabatan Peminjam', $csvContent);
        $this->assertStringContainsString('Kode Barang BMN', $csvContent);
        $this->assertStringContainsString('Barang BMN & NUP', $csvContent);
        $this->assertStringContainsString('Jenis & Periode Peminjaman', $csvContent);

        // Pastikan UUID tidak dipotong dengan titik-titik
        $this->assertStringContainsString('test-uuid-loan-export', $csvContent);
        $this->assertStringNotContainsString('test-uui...', $csvContent);
    }
}
