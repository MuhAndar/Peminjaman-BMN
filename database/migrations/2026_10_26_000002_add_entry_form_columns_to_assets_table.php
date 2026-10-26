<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('jenis_bmn')->nullable()->after('category_code');
            $table->string('kode_satker')->nullable()->after('jenis_bmn');
            $table->string('nama_satker')->nullable()->after('kode_satker');
            $table->string('status_bmn')->nullable()->after('nup');
            $table->string('umur_aset')->nullable()->after('condition');
            $table->string('intra_extra')->nullable()->after('umur_aset');
            $table->string('henti_guna')->nullable()->after('intra_extra');
            $table->string('no_dokumen')->nullable()->after('henti_guna');
            $table->string('no_bpkp')->nullable()->after('no_dokumen');
            $table->date('tgl_perolehan')->nullable()->after('acquisition_year');
            $table->decimal('nilai_buku', 16, 0)->nullable()->after('acquisition_value');
            $table->string('status_penggunaan')->nullable()->after('nilai_buku');
            $table->string('no_psp')->nullable()->after('status_penggunaan');
            $table->date('tgl_psp')->nullable()->after('no_psp');
            $table->text('alamat')->nullable()->after('tgl_psp');
            $table->string('lokasi_ruang')->nullable()->after('alamat');
            $table->string('no_stnk')->nullable()->after('lokasi_ruang');
            $table->string('nama_pengguna')->nullable()->after('no_stnk');
            $table->json('extra_attributes')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_bmn',
                'kode_satker',
                'nama_satker',
                'status_bmn',
                'umur_aset',
                'intra_extra',
                'henti_guna',
                'no_dokumen',
                'no_bpkp',
                'tgl_perolehan',
                'nilai_buku',
                'status_penggunaan',
                'no_psp',
                'tgl_psp',
                'alamat',
                'lokasi_ruang',
                'no_stnk',
                'nama_pengguna',
                'extra_attributes',
            ]);
        });
    }
};
