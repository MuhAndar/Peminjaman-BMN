<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LoanItem;
use App\Models\LoanPhoto;
use App\Models\LoanRequest;
use App\Models\User;
use App\Services\LoanStateMachine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    /**
     * Helper to create an active loan for testing.
     */
    protected function createActiveLoan(Employee $borrower, User $pengelola, User $ketuaTim): LoanRequest
    {
        $asset = Asset::where('status', 'AVAILABLE')->first();

        $loan = LoanRequest::create([
            'borrower_id' => $borrower->id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'DRAFT',
            'snap_full_name' => $borrower->full_name,
            'snap_nip' => $borrower->nip,
            'snap_rank_grade' => $borrower->rank_grade,
            'snap_position' => $borrower->position,
            'snap_home_address' => $borrower->home_address,
        ]);

        LoanItem::create([
            'loan_request_id' => $loan->id,
            'asset_id' => $asset->id,
            'purpose' => 'Dinas lapangan',
            'condition_out' => 'Baik',
            'snap_item_name' => $asset->item_name,
            'snap_brand_model' => $asset->brand_model_combined,
            'snap_nup' => $asset->nup,
            'snap_year' => $asset->acquisition_year,
            'snap_value' => $asset->acquisition_value,
            'is_blocking' => false,
        ]);

        $borrowerUser = $borrower->user;
        LoanStateMachine::transition($loan, 'SUBMITTED', $borrowerUser);
        LoanStateMachine::transition($loan, 'UNDER_APPROVAL', $pengelola);
        LoanStateMachine::transition($loan, 'APPROVED', $ketuaTim);
        LoanStateMachine::transition($loan, 'BAST_ISSUED', $pengelola);
        LoanStateMachine::transition($loan, 'ACTIVE', $pengelola);

        return $loan;
    }

    /**
     * Item 1: Pengembalian hanya pada role peminjam pemilik.
     */
    public function test_only_borrower_who_owns_loan_can_request_return(): void
    {
        $peminjamUser = User::where('role', 'PEMINJAM')->first();
        $otherPeminjamUser = User::where('role', 'PEMINJAM')->skip(1)->first();
        $pengelolaUser = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaTimUser = User::where('role', 'KETUA_TIM_ASET')->first();

        $loan = $this->createActiveLoan($peminjamUser->employee, $pengelolaUser, $ketuaTimUser);
        $this->assertEquals('ACTIVE', $loan->status);

        // 1. Pengelola BMN tidak boleh mengajukan pengembalian
        $this->actingAs($pengelolaUser);
        $resPengelola = $this->post(route('loans.request-return', $loan->id));
        $resPengelola->assertSessionHas('error');
        $loan->refresh();
        $this->assertEquals('ACTIVE', $loan->status);

        // 2. Peminjam lain yang bukan pemilik tidak boleh mengajukan pengembalian
        if ($otherPeminjamUser) {
            $this->actingAs($otherPeminjamUser);
            $resOther = $this->post(route('loans.request-return', $loan->id));
            $resOther->assertSessionHas('error');
            $loan->refresh();
            $this->assertEquals('ACTIVE', $loan->status);
        }

        // 3. Peminjam pemilik sah berhak mengajukan pengembalian
        $this->actingAs($peminjamUser);
        $resOwner = $this->post(route('loans.request-return', $loan->id));
        $resOwner->assertRedirect();
        $loan->refresh();
        $this->assertEquals('RETURN_REQUESTED', $loan->status);
    }

    /**
     * Item 2: Fitur pengambilan & pengunggahan foto BAST (peminjaman & pengembalian).
     */
    public function test_bast_photo_upload_and_delete(): void
    {
        $peminjamUser = User::where('role', 'PEMINJAM')->first();
        $pengelolaUser = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaTimUser = User::where('role', 'KETUA_TIM_ASET')->first();

        $loan = $this->createActiveLoan($peminjamUser->employee, $pengelolaUser, $ketuaTimUser);

        $this->actingAs($peminjamUser);

        // Unggah foto BAST Peminjaman
        $photo1 = UploadedFile::fake()->image('bast_serah_terima_1.jpg', 800, 600);
        $photo2 = UploadedFile::fake()->image('bast_serah_terima_2.png', 800, 600);

        $response = $this->post(route('loans.photos.upload', $loan->id), [
            'stage' => 'BAST_PEMINJAMAN',
            'caption' => 'Kondisi fisik laptop saat serah terima',
            'photos' => [$photo1, $photo2],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseCount('loan_photos', 2);

        $createdPhoto = LoanPhoto::where('loan_request_id', $loan->id)->first();
        $this->assertEquals('BAST_PEMINJAMAN', $createdPhoto->stage);
        $this->assertEquals('Kondisi fisik laptop saat serah terima', $createdPhoto->caption);
        Storage::disk('public')->assertExists($createdPhoto->file_path);

        // Hapus foto
        $delResponse = $this->delete(route('loans.photos.delete', $createdPhoto->id));
        $delResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('loan_photos', ['id' => $createdPhoto->id]);
        Storage::disk('public')->assertMissing($createdPhoto->file_path);
    }

    /**
     * Item 3: Menu penambahan tanda tangan manual (kanvas / profil / upload berkas).
     */
    public function test_signature_management_in_profile_and_loan_signing(): void
    {
        $user = User::where('role', 'PEMINJAM')->first();
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaTim = User::where('role', 'KETUA_TIM_ASET')->first();
        $loan = $this->createActiveLoan($user->employee, $pengelola, $ketuaTim);

        $this->actingAs($user);

        // 1. Simpan tanda tangan kanvas pada profil
        $dummyBase64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $resProfile = $this->post(route('profile.signature.update'), [
            'source' => 'pad',
            'signature_data' => $dummyBase64Png,
        ]);
        $resProfile->assertSessionHas('success');
        $user->refresh();
        $this->assertNotNull($user->signature_path);
        Storage::disk('public')->assertExists($user->signature_path);

        // 2. Bubuhkan tanda tangan ke dokumen menggunakan tanda tangan dari profil
        $resSignProfile = $this->post(route('loans.sign', $loan->id), [
            'sign_as' => 'borrower',
            'source' => 'profile',
        ]);
        $resSignProfile->assertSessionHas('success');
        $loan->refresh();
        $this->assertEquals($user->signature_path, $loan->borrower_signature);

        // 3. Bubuhkan tanda tangan kanvas langsung pada modal peminjaman
        $resSignPad = $this->post(route('loans.sign', $loan->id), [
            'sign_as' => 'borrower',
            'source' => 'pad',
            'signature_data' => $dummyBase64Png,
        ]);
        $resSignPad->assertSessionHas('success');
        $loan->refresh();
        $this->assertNotNull($loan->borrower_signature);

        // 4. Petugas Pengelola BMN menandatangani sebagai officer
        $this->actingAs($pengelola);
        $resSignOfficer = $this->post(route('loans.sign', $loan->id), [
            'sign_as' => 'officer',
            'source' => 'pad',
            'signature_data' => $dummyBase64Png,
        ]);
        $resSignOfficer->assertSessionHas('success');
        $loan->refresh();
        $this->assertNotNull($loan->officer_signature);
    }

    /**
     * Item 4: Ekspor rekapan audit aktivitas peminjaman (bulan, tahun, tanggal spesifik, xlsx, csv).
     */
    public function test_audit_logs_export_with_filters(): void
    {
        $admin = User::where('role', 'ADMIN')->first();
        $this->actingAs($admin);

        AuditLog::record('EXPORT_TEST_ACTION_1', 'LoanRequest', 'test-uuid-1', null, ['detail' => 'Sample 1']);
        AuditLog::record('EXPORT_TEST_ACTION_2', 'LoanRequest', 'test-uuid-2', null, ['detail' => 'Sample 2']);

        // 1. Ekspor mode Per Bulan (XLSX)
        $resMonthXlsx = $this->get(route('admin.audit-logs.export', [
            'filter_mode' => 'month',
            'month' => date('n'),
            'year' => date('Y'),
            'format' => 'xlsx',
        ]));
        $resMonthXlsx->assertStatus(200);
        $this->assertTrue(str_contains($resMonthXlsx->headers->get('content-disposition'), '.xlsx'));

        // 2. Ekspor mode Per Tahun (XLSX)
        $resYearXlsx = $this->get(route('admin.audit-logs.export', [
            'filter_mode' => 'year',
            'year' => date('Y'),
            'format' => 'xlsx',
        ]));
        $resYearXlsx->assertStatus(200);

        // 3. Ekspor mode Specific Date Range (CSV)
        $resRangeCsv = $this->get(route('admin.audit-logs.export', [
            'filter_mode' => 'date_range',
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'format' => 'csv',
        ]));
        $resRangeCsv->assertStatus(200);
        $this->assertTrue(str_contains($resRangeCsv->headers->get('content-disposition'), '.csv'));
        $content = $resRangeCsv->streamedContent();
        $this->assertStringContainsString('EXPORT_TEST_ACTION_1', $content);
    }

    /**
     * Item 5: Fitur upload data melalui .xlsx & download template starter.
     */
    public function test_excel_upload_and_template_download(): void
    {
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $this->actingAs($pengelola);

        // 1. Unduh template starter .xlsx
        $resTemplate = $this->get(route('kelola.import.template'));
        $resTemplate->assertStatus(200);
        $this->assertTrue(str_contains($resTemplate->headers->get('content-disposition'), '.xlsx'));

        // 2. Unggah file .xlsx untuk pratinjau parser
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data_BMN');
        $sheet->fromArray([
            ['Kode Barang', 'NUP', 'Nama Barang', 'Merk/Tipe', 'Tahun', 'Nilai Perolehan'],
            ['3.05.01.05.002', '12', 'Laptop ASUS Zenbook', 'Zenbook 14 UM425', '2023', '18500000'],
            ['3.05.01.05.003', '4', 'Printer Epson L3210', 'L3210 EcoTank', '2022', '2400000'],
        ]);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'test_bmn_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);

        $uploadedFile = new UploadedFile($tempFilePath, 'Data_BMN_Test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $resUpload = $this->post(route('kelola.import.upload'), [
            'file' => $uploadedFile,
            'data_type' => 'assets',
        ]);

        $resUpload->assertStatus(200);
        $resUpload->assertSee('Data_BMN_Test.xlsx');
        $resUpload->assertSee('Laptop ASUS Zenbook');
        $resUpload->assertSee('Printer Epson L3210');
        $resUpload->assertSee('Kode Barang');

        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
    }

    /**
     * Test cetak PDF BAST Peminjaman & Pengembalian dengan spesimen tanda tangan digital terpasang.
     */
    public function test_pdf_generation_with_digital_signatures(): void
    {
        $user = User::where('role', 'PEMINJAM')->first();
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaTim = User::where('role', 'KETUA_TIM_ASET')->first();
        $loan = $this->createActiveLoan($user->employee, $pengelola, $ketuaTim);

        $dummyBase64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Tanda tangani sebagai borrower & officer
        $this->actingAs($user);
        $this->post(route('loans.sign', $loan->id), [
            'sign_as' => 'borrower',
            'source' => 'pad',
            'signature_data' => $dummyBase64Png,
        ]);

        $this->actingAs($pengelola);
        $this->post(route('loans.sign', $loan->id), [
            'sign_as' => 'officer',
            'source' => 'pad',
            'signature_data' => $dummyBase64Png,
        ]);

        $loan->refresh();
        $this->assertNotNull($loan->borrower_signature);
        $this->assertNotNull($loan->officer_signature);

        // Akses cetak PDF BAST Peminjaman
        $resPdfBast = $this->get(route('document.pdf', [$loan->id, 'BAST_PEMINJAMAN']));
        $resPdfBast->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdfBast->headers->get('content-type'));

        // Akses cetak PDF Surat Peminjaman
        $resPdfSurat = $this->get(route('document.pdf', [$loan->id, 'SURAT_PEMINJAMAN']));
        $resPdfSurat->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdfSurat->headers->get('content-type'));
    }
}
