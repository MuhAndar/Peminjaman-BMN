<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah signature_path pada users
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('is_active');
        });

        // 2. Tambah kolom tanda tangan digital pada loan_requests
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->text('borrower_signature')->nullable()->after('snap_home_address');
            $table->text('officer_signature')->nullable()->after('borrower_signature');
        });

        // 3. Buat tabel loan_photos untuk dokumentasi BAST Peminjaman & Pengembalian
        Schema::create('loan_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_request_id')->constrained('loan_requests')->cascadeOnDelete();
            $table->string('stage'); // BAST_PEMINJAMAN | BAST_PENGEMBALIAN | GENERAL
            $table->string('file_path');
            $table->string('original_name');
            $table->string('caption')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_photos');

        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['borrower_signature', 'officer_signature']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path']);
        });
    }
};
