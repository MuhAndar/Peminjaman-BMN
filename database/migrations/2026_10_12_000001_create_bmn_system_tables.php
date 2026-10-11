<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Employees (Master Data Pegawai)
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nip', 18)->nullable()->unique(); // NULL jika PPNPN (16 digit NIK tidak disimpan sebagai NIP)
            $table->string('full_name');
            $table->string('rank_grade')->nullable(); // Penata Tk. I, III/d
            $table->string('position')->nullable();   // Jabatan
            $table->text('home_address')->nullable();
            $table->string('employment_status');      // PNS | CPNS | PPPK | PPNPN
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Update Users Table for Employee relation & Role
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('employee_id')->nullable()->after('id')->constrained('employees')->nullOnDelete();
            $table->string('role')->default('PEMINJAM')->after('email'); // PEMINJAM | PENGELOLA_BMN | KETUA_TIM_ASET | ADMIN | PIMPINAN
            $table->boolean('is_active')->default(true)->after('role');
        });

        // 2. Asset Categories
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->string('code')->primary(); // LAPTOP | MOBILE_PHONE | CAR | MOTORCYCLE
            $table->string('label');
            $table->string('default_loan_type'); // ANNUAL | SHORT_TERM
            $table->boolean('requires_schedule_check')->default(false);
            $table->timestamps();
        });

        // 3. Assets (Data BMN)
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category_code');
            $table->foreign('category_code')->references('code')->on('asset_categories');
            $table->string('item_name');             // Lap Top, Mobil Dinas, dll.
            $table->string('brand')->nullable();     // Merk
            $table->string('model')->nullable();     // Type
            $table->string('bmn_code', 10)->nullable();
            $table->integer('nup')->nullable();
            $table->string('plate_number')->nullable()->unique(); // Plat nomor kendaraan
            $table->integer('acquisition_year');     // Tahun perolehan
            $table->decimal('acquisition_value', 16, 0); // Nilai (Rupiah)
            $table->string('condition')->default('BAIK'); // BAIK | RUSAK | TIDAK_LENGKAP_HILANG
            $table->string('status')->default('AVAILABLE'); // AVAILABLE | ON_LOAN | NEEDS_FOLLOW_UP | INACTIVE
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['bmn_code', 'nup']);
        });

        // 4. Signatories (Pejabat Penandatangan Dokumen)
        Schema::create('signatories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role'); // KETUA_TIM_KEUANGAN_ASET | PENGELOLA_ASET
            $table->foreignUuid('employee_id')->constrained('employees');
            $table->string('title_line'); // e.g. "Ketua Tim Keuangan dan Aset BBPSDMP Makassar,"
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });

        // 5. Loan Requests (Pengajuan Peminjaman BMN)
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('borrower_id')->constrained('employees');
            $table->string('loan_type'); // ANNUAL | SHORT_TERM
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('DRAFT');
            // DRAFT | SUBMITTED | NEEDS_REVISION | UNDER_APPROVAL | APPROVED | BAST_ISSUED | ACTIVE | RETURN_REQUESTED | RETURN_CHECKED | RETURNED | REJECTED | CANCELLED

            // Snapshot data diri peminjam saat pengajuan dibuat (BR-07)
            $table->string('snap_full_name');
            $table->string('snap_nip', 18)->nullable();
            $table->string('snap_rank_grade')->nullable();
            $table->string('snap_position')->nullable();
            $table->text('snap_home_address')->nullable();

            $table->text('review_note')->nullable();   // Catatan telaah Pengelola BMN
            $table->text('decision_note')->nullable(); // Catatan persetujuan Ketua Tim
            $table->boolean('is_legacy')->default(false);
            $table->integer('legacy_seq')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // 6. Loan Items (Barang yang dipinjam dalam satu pengajuan)
        Schema::create('loan_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_request_id')->constrained('loan_requests')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets');
            $table->string('purpose'); // Keperluan peminjaman
            $table->string('condition_out')->default('Baik'); // Kondisi saat diserahkan
            $table->string('condition_in')->nullable();       // Kondisi saat dikembalikan
            $table->text('follow_up_note')->nullable();       // Tindak lanjut jika rusak/hilang (BR-10)

            // Snapshot data barang saat pengajuan dibuat (BR-07)
            $table->string('snap_item_name');
            $table->string('snap_brand_model')->nullable();
            $table->integer('snap_nup')->nullable();
            $table->integer('snap_year')->nullable();
            $table->decimal('snap_value', 16, 0)->nullable();

            $table->boolean('is_blocking')->default(false); // True saat memegang reservasi barang (BR-02)
            $table->timestamps();

            $table->unique(['loan_request_id', 'asset_id']);
        });

        // 7. Loan Status History (Audit trail transisi status)
        Schema::create('loan_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('loan_request_id')->constrained('loan_requests')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 8. Documents (3 Dokumen Resmi)
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_request_id')->constrained('loan_requests')->cascadeOnDelete();
            $table->string('doc_type'); // SURAT_PEMINJAMAN | BAST_PEMINJAMAN | BAST_PENGEMBALIAN
            $table->string('doc_number');
            $table->date('doc_date');
            $table->string('status')->default('ISSUED'); // ISSUED | VOID
            $table->string('generated_file_key')->nullable();
            $table->string('signed_file_key')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();

            $table->unique(['doc_type', 'doc_number']);
        });

        // 9. Document Sequences (Penomoran atomik BR-06)
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('doc_type');
            $table->integer('year');
            $table->integer('last_value')->default(0);
            $table->primary(['doc_type', 'year']);
        });

        // 10. App Settings (Pengaturan Kop, SLA, Format Nomor)
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });

        // 11. Audit Logs (Jejak audit sistem)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity');
            $table->string('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('loan_status_history');
        Schema::dropIfExists('loan_items');
        Schema::dropIfExists('loan_requests');
        Schema::dropIfExists('signatories');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['employee_id', 'role', 'is_active']);
        });

        Schema::dropIfExists('employees');
    }
};
