<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Employee;
use App\Models\LoanItem;
use App\Models\LoanPhoto;
use App\Models\LoanRequest;
use App\Models\User;
use App\Services\LoanStateMachine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

        // Unggah foto BAST Peminjaman (uji ketahanan array berkas bila ada input kosong)
        $photo1 = UploadedFile::fake()->image('bast_serah_terima_1.jpg', 800, 600);
        $photo2 = UploadedFile::fake()->image('bast_serah_terima_2.png', 800, 600);
        $emptyFile = new UploadedFile('', '', null, UPLOAD_ERR_NO_FILE, true);

        $response = $this->post(route('loans.photos.upload', $loan->id), [
            'stage' => 'BAST_PEMINJAMAN',
            'caption' => 'Kondisi fisik laptop saat serah terima',
            'photos' => [$photo1, $photo2, $emptyFile],
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
        $ketuaTim = User::where('role', 'KETUA_TIM_ASET')->first();
        $peminjam = User::where('role', 'PEMINJAM')->first();

        // 1. Pengelola & Ketua Tim dapat mengunduh rekapan katalog BMN .xlsx
        $this->actingAs($ketuaTim);
        $resKetuaExport = $this->get(route('assets.export'));
        $resKetuaExport->assertStatus(200);
        $this->assertTrue(str_contains($resKetuaExport->headers->get('content-disposition'), 'Katalog_Inventaris_BMN_Makassar'));

        $this->actingAs($pengelola);
        $resTemplate = $this->get(route('kelola.import.template'));
        $resTemplate->assertStatus(200);
        $this->assertTrue(str_contains($resTemplate->headers->get('content-disposition'), '.xlsx'));

        $resExport = $this->get(route('kelola.import.export'));
        $resExport->assertStatus(200);
        $this->assertTrue(str_contains($resExport->headers->get('content-disposition'), 'Katalog_Inventaris_BMN_Makassar'));

        // 2. Peminjam dilarang mengakses modul impor (403)
        $this->actingAs($peminjam);
        $resForbidden = $this->get(route('kelola.import.index'));
        $resForbidden->assertStatus(403);

        // 3. Pengelola mengunggah berkas Excel untuk diurai ke antrean
        $this->actingAs($pengelola);
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
            'data_type' => 'ASSETS',
        ]);

        $resUpload->assertStatus(200);
        $resUpload->assertSee('Data_BMN_Test.xlsx');
        $resUpload->assertSee('Laptop ASUS Zenbook');
        $resUpload->assertSee('Printer Epson L3210');
        $resUpload->assertSessionHas('bmn_import_pending');

        // 4. Eksekusi commit import ke basis data
        $resCommit = $this->post(route('kelola.import.commit'));
        $resCommit->assertRedirect(route('kelola.import.index'));
        $resCommit->assertSessionHas('import_success');

        // Verifikasi aset tersimpan di tabel database
        $this->assertDatabaseHas('assets', [
            'item_name' => 'Laptop ASUS Zenbook',
            'bmn_code' => '3.05.01.05.002',
            'nup' => 12,
        ]);

        $this->assertDatabaseHas('assets', [
            'item_name' => 'Printer Epson L3210',
            'bmn_code' => '3.05.01.05.003',
            'nup' => 4,
        ]);

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

        // Akses cetak PDF BAST Pengembalian
        $resPdfKembali = $this->get(route('document.pdf', [$loan->id, 'BAST_PENGEMBALIAN']));
        $resPdfKembali->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdfKembali->headers->get('content-type'));
    }

    /**
     * Menu ubah data diri untuk peminjaman di profil peminjam.
     */
    public function test_borrower_can_update_personal_data_for_bmn_documents(): void
    {
        $user = User::where('role', 'PEMINJAM')->first();
        $this->actingAs($user);

        // 1. Kunjungi halaman profil peminjam
        $response = $this->get(route('profile.show'));
        $response->assertStatus(200);
        $response->assertSee('Ubah Data Diri Peminjaman BMN');

        // 2. Kirim update data diri peminjaman
        $updateResponse = $this->put(route('profile.biodata.update'), [
            'full_name' => 'Dr. H. Andi Pratama, S.Kom., M.T.',
            'nip' => '19871112 201201 1 002',
            'rank_grade' => 'Penata Tk. I (III/d)',
            'position' => 'Pranata Komputer Ahli Muda',
            'employment_status' => 'PNS',
            'home_address' => 'Jl. Pengayoman No. 45 Blok B, Panakkukang, Makassar',
            'phone' => '081298765432',
        ]);

        $updateResponse->assertRedirect(route('profile.show', ['tab' => 'biodata']));
        $updateResponse->assertSessionHas('success');

        // 3. Verifikasi data terupdate di database
        $user->refresh();
        $this->assertEquals('Dr. H. Andi Pratama, S.Kom., M.T.', $user->name);
        $employee = $user->employee;
        $this->assertNotNull($employee);
        $this->assertEquals('Dr. H. Andi Pratama, S.Kom., M.T.', $employee->full_name);
        $this->assertEquals('198711122012011002', $employee->nip);
        $this->assertEquals('Penata Tk. I (III/d)', $employee->rank_grade);
        $this->assertEquals('Pranata Komputer Ahli Muda', $employee->position);
        $this->assertEquals('Jl. Pengayoman No. 45 Blok B, Panakkukang, Makassar', $employee->home_address);
        $this->assertEquals('081298765432', $employee->phone);

        // 4. Verifikasi audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UPDATE_PROFILE_BIODATA',
            'entity' => 'Employee',
            'entity_id' => (string)$employee->id,
        ]);

        // 5. Verifikasi pengajuan baru menggunakan snapshot data diri yang baru diperbarui
        $asset = Asset::where('status', 'AVAILABLE')->first();
        $loanRes = $this->post(route('loans.store'), [
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->addDays(1)->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'home_address' => $employee->home_address,
            'asset_ids' => [$asset->id],
            'purposes' => [$asset->id => 'Operasional workshop TIK'],
        ]);
        $loanRes->assertSessionHasNoErrors();

        $loan = LoanRequest::where('borrower_id', $employee->id)->latest('created_at')->first();
        $this->assertEquals('Dr. H. Andi Pratama, S.Kom., M.T.', $loan->snap_full_name);
        $this->assertEquals('198711122012011002', $loan->snap_nip);
        $this->assertEquals('Penata Tk. I (III/d)', $loan->snap_rank_grade);
        $this->assertEquals('Pranata Komputer Ahli Muda', $loan->snap_position);
        $this->assertEquals('Jl. Pengayoman No. 45 Blok B, Panakkukang, Makassar', $loan->snap_home_address);
    }

    /**
     * Uji pembaruan data diri peminjam langsung lewat Langkah 3 Formulir Pengajuan (Tombol Buka Kunci).
     */
    public function test_borrower_can_unlock_and_update_personal_data_during_loan_creation(): void
    {
        $user = User::where('role', 'PEMINJAM')->first();
        $this->actingAs($user);

        $asset = Asset::where('status', 'AVAILABLE')->first();

        $response = $this->post(route('loans.store'), [
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->addDays(2)->toDateString(),
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'asset_ids' => [$asset->id],
            'purposes' => [$asset->id => 'Tugas dinas lapangan pengawasan BMN'],
            // Data diri baru yang diedit saat tombol buka gembok di Langkah 3 diklik
            'full_name' => 'Ir. Andi Pratama Utama, S.T., M.Kom.',
            'nip' => '199201012015031005',
            'rank_grade' => 'Penata Muda Tk. I, III/b',
            'position' => 'Analis Tata Kelola BMN',
            'phone_number' => '082112345678',
            'home_address' => 'Kompleks Pemda BBLSDM No. 12 Makassar',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Verifikasi data employee dan user terupdate
        $user->refresh();
        $this->assertEquals('Ir. Andi Pratama Utama, S.T., M.Kom.', $user->name);
        $this->assertEquals('Ir. Andi Pratama Utama, S.T., M.Kom.', $user->employee->full_name);
        $this->assertEquals('199201012015031005', $user->employee->nip);
        $this->assertEquals('Penata Muda Tk. I, III/b', $user->employee->rank_grade);
        $this->assertEquals('Analis Tata Kelola BMN', $user->employee->position);
        $this->assertEquals('082112345678', $user->employee->phone);
        $this->assertEquals('Kompleks Pemda BBLSDM No. 12 Makassar', $user->employee->home_address);

        // Verifikasi snapshot loan request juga memakai data baru
        $loan = LoanRequest::where('borrower_id', $user->employee->id)->latest('created_at')->first();
        $this->assertEquals('Ir. Andi Pratama Utama, S.T., M.Kom.', $loan->snap_full_name);
        $this->assertEquals('199201012015031005', $loan->snap_nip);
        $this->assertEquals('Penata Muda Tk. I, III/b', $loan->snap_rank_grade);
        $this->assertEquals('Analis Tata Kelola BMN', $loan->snap_position);
        $this->assertEquals('Kompleks Pemda BBLSDM No. 12 Makassar', $loan->snap_home_address);
    }

    /**
     * Uji halaman formulir peminjaman memuat fungsi search dan 5 kategori populer dinamis.
     */
    public function test_loan_create_page_renders_with_search_and_popular_categories(): void
    {
        $user = User::where('role', 'PEMINJAM')->first();
        $this->actingAs($user);

        Cache::forget('bmn_popular_category_codes');
        $response = $this->get(route('loans.create'));
        $response->assertOk();
        $this->assertTrue(Cache::has('bmn_popular_category_codes'));
        $response->assertViewHas('popularCategories');
        $response->assertViewHas('categories');
        $response->assertSee('Filter Cepat Terpopuler:');
        $response->assertSee('Cari nama barang, merk/tipe, NUP, plat nomor...');
        $response->assertSee('Kembali ke Daftar Pengajuan');
        $response->assertSee('toggleCategory');
        $response->assertSee('selectedCategory');
    }

    /**
     * Uji privasi spesimen tanda tangan: tiap role hanya melihat ttd miliknya dan status role lainnya.
     */
    public function test_signature_privacy_rules_between_roles(): void
    {
        $borrowerUser = User::where('role', 'PEMINJAM')->first();
        $pengelolaUser = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaUser = User::where('role', 'KETUA_TIM_ASET')->first();

        $loan = $this->createActiveLoan($borrowerUser->employee, $pengelolaUser, $ketuaUser);
        
        // Simpan path tanda tangan pada loan
        $loan->borrower_signature = 'signatures/borrower_secret_sig.png';
        $loan->officer_signature = 'signatures/officer_secret_sig.png';
        $loan->save();

        // 1. Peminjam: hanya melihat ttd grafis miliknya, bukan milik pengelola
        $this->actingAs($borrowerUser);
        $resBorrower = $this->get(route('loans.show', $loan->id));
        $resBorrower->assertOk();
        $resBorrower->assertSee('signatures/borrower_secret_sig.png');
        $resBorrower->assertDontSee('signatures/officer_secret_sig.png');
        $resBorrower->assertSee('Sudah Ditandatangani');

        // 2. Pengelola: hanya melihat ttd grafis miliknya, bukan milik peminjam
        $this->actingAs($pengelolaUser);
        $resPengelola = $this->get(route('loans.show', $loan->id));
        $resPengelola->assertOk();
        $resPengelola->assertSee('signatures/officer_secret_sig.png');
        $resPengelola->assertDontSee('signatures/borrower_secret_sig.png');
    }

    /**
     * Uji timeline status memuat label dokumen spesifik dan kotak deskripsi untuk catatan/komentar.
     */
    public function test_status_history_displays_document_clarified_labels_and_note_context_descriptions(): void
    {
        $borrowerUser = User::where('role', 'PEMINJAM')->first();
        $pengelolaUser = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaUser = User::where('role', 'KETUA_TIM_ASET')->first();
        $asset = Asset::where('status', 'AVAILABLE')->first();

        $loan = LoanRequest::create([
            'borrower_id' => $borrowerUser->employee->id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'DRAFT',
            'snap_full_name' => $borrowerUser->employee->full_name,
            'snap_nip' => $borrowerUser->employee->nip,
            'snap_rank_grade' => $borrowerUser->employee->rank_grade,
            'snap_position' => $borrowerUser->employee->position,
            'snap_home_address' => $borrowerUser->employee->home_address,
        ]);

        LoanItem::create([
            'loan_request_id' => $loan->id,
            'asset_id' => $asset->id,
            'purpose' => 'Dinas luar',
            'snap_item_name' => $asset->item_name,
            'snap_brand_model' => $asset->brand_model_combined,
            'snap_value' => 5000000,
        ]);

        LoanStateMachine::transition($loan, 'SUBMITTED', $borrowerUser, 'Pengajuan baru diajukan oleh peminjam.');
        LoanStateMachine::transition($loan, 'UNDER_APPROVAL', $pengelolaUser, 'Dokumen telah lengkap dan barang tersedia.');
        LoanStateMachine::transition($loan, 'APPROVED', $ketuaUser, 'Disposisi disetujui oleh Ketua Tim.');

        $this->actingAs($borrowerUser);
        $response = $this->get(route('loans.show', $loan->id));
        $response->assertOk();

        // 1. Verifikasi alur transisi memperjelas dokumen spesifik
        $response->assertSee('Menunggu Telaah Surat Peminjaman');
        $response->assertSee('Menunggu Persetujuan Surat Peminjaman');
        $response->assertSee('Surat Peminjaman Disetujui');

        // 2. Verifikasi deskripsi kontekstual untuk entri yang memiliki komentar/catatan
        $response->assertSee('Catatan Pengajuan Peminjam:');
        $response->assertSee('Catatan Hasil Telaah Pengelola BMN:');
        $response->assertSee('Catatan Disposisi / Persetujuan Ketua Tim:');
    }

    /**
     * Verifikasi Ketua Tim dapat login, mengakses dashboard, dan render menu persetujuan tanpa route exception.
     */
    public function test_ketua_tim_login_and_navigation_renders_cleanly(): void
    {
        $ketuaUser = User::where('role', 'KETUA_TIM_ASET')->first();
        $this->actingAs($ketuaUser);

        // Dashboard Ketua Tim
        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Persetujuan');

        // Halaman Daftar Persetujuan (via nama resmi maupun alias)
        $resOfficial = $this->get(route('persetujuan.index'));
        $resOfficial->assertOk();

        $resAlias = $this->get(route('approval.index'));
        $resAlias->assertOk();
    }

    /**
     * Uji Keamanan Cybersecurity: Proteksi Broken Access Control, URL tampering, dan IDOR dokumen.
     */
    public function test_cybersecurity_access_control_and_url_authorization(): void
    {
        $peminjamA = User::where('role', 'PEMINJAM')->first();
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $ketua = User::where('role', 'KETUA_TIM_ASET')->first();

        // Buat peminjam B
        $peminjamB = User::factory()->create([
            'role' => 'PEMINJAM',
            'email' => 'peminjam.b@test.id',
        ]);

        $asset = Asset::where('status', 'AVAILABLE')->first();
        $loanA = LoanRequest::create([
            'borrower_id' => $peminjamA->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'status' => 'APPROVED',
            'snap_full_name' => $peminjamA->name,
            'snap_nip' => $peminjamA->employee->nip,
        ]);

        // Buat dokumen untuk loan A
        $doc = Document::create([
            'loan_request_id' => $loanA->id,
            'doc_type' => 'SURAT_PEMINJAMAN',
            'doc_number' => '099/BMN-PJM/BBLSDM.73/X/2026',
            'doc_date' => Carbon::now()->toDateString(),
            'status' => 'ISSUED',
        ]);

        // 1. Peminjam B login dan mencoba akses URL antrean telaah pengelola -> harus 403
        $this->actingAs($peminjamB);
        $resQueue = $this->get(route('kelola.queue'));
        $resQueue->assertStatus(403);

        // 2. Peminjam B mencoba akses URL antrean persetujuan ketua tim -> harus 403
        $resApproval = $this->get(route('persetujuan.index'));
        $resApproval->assertStatus(403);

        // 3. IDOR Check: Peminjam B mencoba mengunduh PDF dokumen peminjaman milik peminjam A via URL -> harus 403
        $resDocOther = $this->get(route('document.pdf', [$loanA->id, 'SURAT_PEMINJAMAN']));
        $resDocOther->assertStatus(403);

        // 4. Peminjam A mengunduh dokumen miliknya sendiri -> harus 200
        $this->actingAs($peminjamA);
        $resDocSelf = $this->get(route('document.pdf', [$loanA->id, 'SURAT_PEMINJAMAN']));
        $resDocSelf->assertStatus(200);

        // 5. Pengelola dan Ketua Tim berhak mengakses antrean tugasnya
        $this->actingAs($pengelola);
        $this->get(route('kelola.queue'))->assertOk();

        $this->actingAs($ketua);
        $this->get(route('persetujuan.index'))->assertOk();
    }

    /**
     * Uji Ketua Tim dapat menandatangani digital saat menyetujui telaah dan tersimpan di data pengajuan.
     */
    public function test_ketua_tim_can_sign_digitally_during_approval(): void
    {
        $peminjam = User::where('role', 'PEMINJAM')->first();
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $ketua = User::where('role', 'KETUA_TIM_ASET')->first();
        $asset = Asset::where('status', 'AVAILABLE')->first();

        $loan = LoanRequest::create([
            'borrower_id' => $peminjam->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'status' => 'UNDER_APPROVAL',
            'snap_full_name' => $peminjam->name,
            'snap_nip' => $peminjam->employee->nip,
        ]);

        LoanItem::create([
            'loan_request_id' => $loan->id,
            'asset_id' => $asset->id,
            'purpose' => 'Dinas',
            'snap_item_name' => $asset->item_name,
            'snap_brand_model' => $asset->brand_model_combined,
            'snap_value' => 1000000,
        ]);

        $this->actingAs($ketua);

        // Halaman telaah persetujuan memuat modal tanda tangan popup resmi
        $showRes = $this->get(route('persetujuan.show', $loan->id));
        $showRes->assertOk();
        $showRes->assertSee('Bubuhkan Tanda Tangan Digital');
        $showRes->assertSee('Ketua Tim Keuangan & Aset', false);

        // Dummy canvas Base64 signature
        $dummyBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->post(route('persetujuan.approve', $loan->id), [
            'note' => 'Disetujui untuk dinas luar kota',
            'signature_source' => 'pad',
            'signature_data' => $dummyBase64,
            'save_to_profile' => 1,
        ]);

        $response->assertRedirect(route('persetujuan.index'));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertEquals('APPROVED', $loan->status);
        $this->assertNotNull($loan->approver_signature);
        $this->assertStringContainsString('signatures/approver_', $loan->approver_signature);

        // Verifikasi dokumen Surat Peminjaman terbit dengan tanda tangan Ketua Tim
        $pdfRes = $this->get(route('document.pdf', [$loan->id, 'SURAT_PEMINJAMAN']));
        $pdfRes->assertOk();
    }

    /**
     * Uji pencegahan terbitnya dokumen resmi Surat Peminjaman jika tanda tangan Ketua Tim kosong.
     */
    public function test_ketua_tim_cannot_approve_without_signature(): void
    {
        $peminjam = User::where('role', 'PEMINJAM')->first();
        $ketua = User::where('role', 'KETUA_TIM_ASET')->first();
        $ketua->signature_path = null;
        $ketua->save();

        $asset = Asset::where('status', 'AVAILABLE')->first();

        $loan = LoanRequest::create([
            'borrower_id' => $peminjam->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'status' => 'UNDER_APPROVAL',
            'snap_full_name' => $peminjam->name,
            'snap_nip' => $peminjam->employee->nip,
        ]);

        $this->actingAs($ketua);

        // Percobaan persetujuan tanpa tanda tangan (source kosong / null)
        $response = $this->from(route('persetujuan.show', $loan->id))
            ->post(route('persetujuan.approve', $loan->id), [
                'note' => 'Disetujui tanpa ttd',
                'signature_source' => 'profile',
            ]);

        $response->assertRedirect(route('persetujuan.show', $loan->id));
        $response->assertSessionHas('error');

        $loan->refresh();
        // Status harus tetap UNDER_APPROVAL dan Surat Peminjaman tidak boleh terbit
        $this->assertEquals('UNDER_APPROVAL', $loan->status);
        $this->assertNull($loan->approver_signature);
        $this->assertDatabaseMissing('documents', [
            'loan_request_id' => $loan->id,
            'doc_type' => 'SURAT_PEMINJAMAN',
        ]);
    }

    /**
     * Uji riwayat telaah Ketua Tim (tab waiting, approved, rejected, all) dan data tetap ada.
     */
    public function test_ketua_tim_history_tabs_and_data_retention(): void
    {
        $ketua = User::where('role', 'KETUA_TIM_ASET')->first();
        $this->actingAs($ketua);

        // Akses tab waiting
        $resWaiting = $this->get(route('persetujuan.index', ['tab' => 'waiting']));
        $resWaiting->assertOk();
        $resWaiting->assertSee('Perlu Keputusan');

        // Akses tab approved (riwayat disetujui)
        $resApproved = $this->get(route('persetujuan.index', ['tab' => 'approved']));
        $resApproved->assertOk();
        $resApproved->assertSee('Riwayat Disetujui');

        // Akses tab rejected (riwayat ditolak/revisi)
        $resRejected = $this->get(route('persetujuan.index', ['tab' => 'rejected']));
        $resRejected->assertOk();
        $resRejected->assertSee('Riwayat Ditolak');

        // Akses tab all
        $resAll = $this->get(route('persetujuan.index', ['tab' => 'all']));
        $resAll->assertOk();
        $resAll->assertSee('Semua Pengajuan');
    }

    /**
     * Uji ekspor seluruh inventaris di /kelola/import-data/export berhasil tanpa error 500.
     */
    public function test_inventory_export_success_without_500_error(): void
    {
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $this->actingAs($pengelola);

        $resExport = $this->get(route('kelola.import.export'));
        $resExport->assertOk();
        $resExport->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Uji halaman riwayat pengajuan peminjam dan antrean pengelola dengan filter status.
     */
    public function test_user_and_pengelola_history_views_and_filters(): void
    {
        $peminjam = User::where('role', 'PEMINJAM')->first();
        $this->actingAs($peminjam);

        $resUserAll = $this->get(route('loans.index', ['status' => 'all']));
        $resUserAll->assertOk();
        $resUserAll->assertSee('Riwayat Pengajuan Saya');

        $resUserReturned = $this->get(route('loans.index', ['status' => 'returned']));
        $resUserReturned->assertOk();
        $resUserReturned->assertSee('Selesai / Dikembalikan');

        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();
        $this->actingAs($pengelola);

        $resQueueAll = $this->get(route('kelola.queue'));
        $resQueueAll->assertOk();
        $resQueueAll->assertSee('Antrean &amp; Riwayat Pengelolaan BMN', false);

        $resQueueReturned = $this->get(route('kelola.queue', ['status' => 'RETURNED']));
        $resQueueReturned->assertOk();
        $resQueueReturned->assertSee('Selesai / Riwayat Arsip');
    }

    /**
     * Uji draft tanda tangan Ketua Tim tersimpan secara autosave sehingga saat refresh halaman ttd tidak hilang.
     */
    public function test_ketua_tim_draft_signature_persists_across_refresh_and_allows_approval(): void
    {
        $ketuaTim = User::where('role', 'KETUA_TIM_ASET')->first();
        $user = User::where('role', 'PEMINJAM')->first();
        $pengelola = User::where('role', 'PENGELOLA_BMN')->first();

        // Siapkan pengajuan dalam status UNDER_APPROVAL
        $loan = $this->createActiveLoan($user->employee, $pengelola, $ketuaTim);
        $loan->status = 'UNDER_APPROVAL';
        $loan->approver_signature = null;
        $loan->save();

        $this->actingAs($ketuaTim);

        $dummyBase64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // 1. Ketua Tim mengklik "Bubuhkan ke Dokumen" yang mengirim autosave draft signature
        $resDraft = $this->postJson(route('persetujuan.draft-signature', $loan->id), [
            'signature_source' => 'pad',
            'signature_data' => $dummyBase64Png,
            'save_to_profile' => false,
        ]);

        $resDraft->assertOk();
        $resDraft->assertJson(['ok' => true]);

        // Verifikasi tanda tangan tersimpan di model loan_requests
        $loanFresh = $loan->fresh();
        $this->assertNotNull($loanFresh->approver_signature);
        Storage::disk('public')->assertExists($loanFresh->approver_signature);

        // 2. Simulasi refresh halaman: panggil GET show
        $resShow = $this->get(route('persetujuan.show', $loan->id));
        $resShow->assertOk();
        $resShow->assertSee($loanFresh->approver_signature);
        $resShow->assertSee('Opsi 2: Kembalikan (Perlu Perbaikan)');
        $resShow->assertSee('Opsi 3: Tolak Pengajuan Final');

        // 3. Setujui pengajuan tanpa mengirim ulang ttd (menggunakan ttd yang sudah terpasang)
        $resApprove = $this->post(route('persetujuan.approve', $loan->id), [
            'note' => 'Disetujui menggunakan draft tanda tangan yang sudah terpasang',
        ]);

        $resApprove->assertRedirect(route('persetujuan.index'));
        $this->assertEquals('APPROVED', $loan->fresh()->status);
        $this->assertEquals($loanFresh->approver_signature, $loan->fresh()->approver_signature);
    }

    public function test_login_page_renders_manual_form_without_demo_switcher_and_has_testing_accounts(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertDontSee('Masuk Cepat Demo (Sesuai Peran SOP):');
        $response->assertSee('Masuk ke Akun');
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('Akun Pengujian (Testing 4 Role)');
        $response->assertSee('peminjam@komdigi.go.id');
        $response->assertSee('pengelola@komdigi.go.id');
        $response->assertSee('ketuatim@komdigi.go.id');
        $response->assertSee('admin@komdigi.go.id');
    }
}

