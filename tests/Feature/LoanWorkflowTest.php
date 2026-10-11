<?php

namespace Tests\Feature;

use App\Helpers\FormatHelper;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Employee;
use App\Models\LoanItem;
use App\Models\LoanRequest;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\LoanStateMachine;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_format_helpers()
    {
        // 1. NIP formatting (8-6-1-3 digit)
        $this->assertEquals('19820608 200901 2 001', FormatHelper::formatNip('198206082009012001'));
        $this->assertEquals('-', FormatHelper::formatNip(null));

        // 2. Rupiah formatting
        $this->assertEquals('Rp 18.500.000', FormatHelper::formatRupiah(18500000));

        // 3. Roman numerals
        $this->assertEquals('X', FormatHelper::toRoman(10));
        $this->assertEquals('I', FormatHelper::toRoman(1));
    }

    public function test_document_numbering_is_atomic_and_sequential()
    {
        $num1 = DocumentNumberService::generateNextNumber('SURAT_PEMINJAMAN');
        $num2 = DocumentNumberService::generateNextNumber('SURAT_PEMINJAMAN');

        $this->assertStringContainsString('001/BMN-PJM/BBPSDMP.73/', $num1);
        $this->assertStringContainsString('002/BMN-PJM/BBPSDMP.73/', $num2);
    }

    public function test_complete_sop_workflow_transitions()
    {
        $peminjamUser = User::where('role', 'PEMINJAM')->first();
        $pengelolaUser = User::where('role', 'PENGELOLA_BMN')->first();
        $ketuaTimUser = User::where('role', 'KETUA_TIM_ASET')->first();
        $asset = Asset::where('status', 'AVAILABLE')->first();

        // 1. Peminjam creates loan request
        $loan = LoanRequest::create([
            'borrower_id' => $peminjamUser->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'end_date' => Carbon::now('Asia/Makassar')->addDays(3)->toDateString(),
            'status' => 'DRAFT',
            'snap_full_name' => $peminjamUser->name,
            'snap_nip' => $peminjamUser->employee->nip,
        ]);

        LoanItem::create([
            'loan_request_id' => $loan->id,
            'asset_id' => $asset->id,
            'purpose' => 'Koordinasi dinas luar kota',
            'snap_item_name' => $asset->item_name,
            'snap_brand_model' => $asset->brand_model_combined,
            'snap_value' => $asset->acquisition_value,
        ]);

        // DRAFT -> SUBMITTED (SOP-1)
        LoanStateMachine::transition($loan, 'SUBMITTED', $peminjamUser);
        $loan->refresh();
        $this->assertEquals('SUBMITTED', $loan->status);
        $this->assertTrue($loan->items->first()->is_blocking);

        // SUBMITTED -> UNDER_APPROVAL (SOP-2 & 3: Pengelola menelaah)
        LoanStateMachine::transition($loan, 'UNDER_APPROVAL', $pengelolaUser, 'Dokumen lengkap');
        $loan->refresh();
        $this->assertEquals('UNDER_APPROVAL', $loan->status);

        // UNDER_APPROVAL -> APPROVED (SOP-4: Ketua Tim menyetujui)
        LoanStateMachine::transition($loan, 'APPROVED', $ketuaTimUser, 'Disetujui');
        $loan->refresh();
        $this->assertEquals('APPROVED', $loan->status);

        // Verify Surat Peminjaman issued atomically
        $suratDoc = Document::where('loan_request_id', $loan->id)->where('doc_type', 'SURAT_PEMINJAMAN')->first();
        $this->assertNotNull($suratDoc);
        $this->assertStringContainsString('BMN-PJM', $suratDoc->doc_number);

        // APPROVED -> BAST_ISSUED (SOP-5: Pengelola menerbitkan BAST)
        LoanStateMachine::transition($loan, 'BAST_ISSUED', $pengelolaUser);
        $loan->refresh();
        $this->assertEquals('BAST_ISSUED', $loan->status);

        $bastDoc = Document::where('loan_request_id', $loan->id)->where('doc_type', 'BAST_PEMINJAMAN')->first();
        $this->assertNotNull($bastDoc);
        $this->assertStringContainsString('BAST-PJM', $bastDoc->doc_number);

        // BAST_ISSUED -> ACTIVE (SOP-6 & 7: Serah terima fisik & tanda tangan)
        LoanStateMachine::transition($loan, 'ACTIVE', $pengelolaUser);
        $loan->refresh();
        $asset->refresh();
        $this->assertEquals('ACTIVE', $loan->status);
        $this->assertEquals('ON_LOAN', $asset->status);

        // ACTIVE -> RETURN_REQUESTED (SOP-Pengembalian 1)
        LoanStateMachine::transition($loan, 'RETURN_REQUESTED', $peminjamUser);
        $loan->refresh();
        $this->assertEquals('RETURN_REQUESTED', $loan->status);

        // RETURN_REQUESTED -> RETURN_CHECKED (SOP-Pengembalian 2 & 3: Cek fisik & buat BAST Pengembalian)
        $item = $loan->items->first();
        LoanStateMachine::transition(
            $loan,
            'RETURN_CHECKED',
            $pengelolaUser,
            'Pengecekan fisik selesai',
            ['items_condition' => [$item->id => ['condition_in' => 'Baik']]]
        );
        $loan->refresh();
        $this->assertEquals('RETURN_CHECKED', $loan->status);

        $bastKblDoc = Document::where('loan_request_id', $loan->id)->where('doc_type', 'BAST_PENGEMBALIAN')->first();
        $this->assertNotNull($bastKblDoc);

        // RETURN_CHECKED -> RETURNED (SOP-Pengembalian 5 & 6: Tutup & simpan barang)
        LoanStateMachine::transition($loan, 'RETURNED', $pengelolaUser);
        $loan->refresh();
        $asset->refresh();
        $this->assertEquals('RETURNED', $loan->status);
        $this->assertEquals('AVAILABLE', $asset->status);
        $this->assertEquals('BAIK', $asset->condition);
    }

    public function test_br_08_prevents_borrower_from_approving_own_loan()
    {
        $ketuaTimUser = User::where('role', 'KETUA_TIM_ASET')->first();
        $asset = Asset::where('status', 'AVAILABLE')->first();

        // Ketua Tim mengajukan peminjaman atas namanya sendiri
        $loan = LoanRequest::create([
            'borrower_id' => $ketuaTimUser->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'end_date' => Carbon::now('Asia/Makassar')->addDays(2)->toDateString(),
            'status' => 'UNDER_APPROVAL',
            'snap_full_name' => $ketuaTimUser->name,
        ]);

        LoanItem::create([
            'loan_request_id' => $loan->id,
            'asset_id' => $asset->id,
            'purpose' => 'Tugas dinas',
            'snap_item_name' => $asset->item_name,
        ]);

        // Ketua Tim mencoba menyetujui pengajuannya sendiri -> harus ditolak oleh guard BR-08!
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pelanggaran BR-08');

        LoanStateMachine::transition($loan, 'APPROVED', $ketuaTimUser);
    }

    public function test_br_02_prevents_overlapping_asset_loans()
    {
        $peminjamUser = User::where('role', 'PEMINJAM')->first();
        $asset = Asset::where('status', 'AVAILABLE')->first();

        // Peminjaman 1: 15-20 Okt
        $loan1 = LoanRequest::create([
            'borrower_id' => $peminjamUser->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => 'DRAFT',
            'snap_full_name' => 'Pegawai 1',
        ]);
        LoanItem::create([
            'loan_request_id' => $loan1->id,
            'asset_id' => $asset->id,
            'purpose' => 'Dinas',
            'snap_item_name' => $asset->item_name,
        ]);
        LoanStateMachine::transition($loan1, 'SUBMITTED', $peminjamUser);

        // Peminjaman 2: 18-22 Okt (Beririsan dengan peminjaman 1 pada tanggal 18-20)
        $loan2 = LoanRequest::create([
            'borrower_id' => $peminjamUser->employee_id,
            'loan_type' => 'SHORT_TERM',
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-22',
            'status' => 'DRAFT',
            'snap_full_name' => 'Pegawai 2',
        ]);
        LoanItem::create([
            'loan_request_id' => $loan2->id,
            'asset_id' => $asset->id,
            'purpose' => 'Dinas overlap',
            'snap_item_name' => $asset->item_name,
        ]);

        // Pengajuan kedua harus ditolak karena bentrok jadwal (BR-02)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Bentrok jadwal peminjaman (BR-02)');

        LoanStateMachine::transition($loan2, 'SUBMITTED', $peminjamUser);
    }
}
