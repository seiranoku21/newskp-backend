<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memastikan kombinasi (nip, tahun, jabatan, unit_kerja, status_kerja) unik.
     *
     * @return void
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();
        $table = 'portofolio_kinerja';
        $indexName = 'portofolio_kinerja_unique_index';

        // Hapus index lama 'nip' jika ada (non-unique), agar bisa diganti unique
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", ['nip']);
                if (!empty($indexes)) {
                    DB::statement("ALTER TABLE {$table} DROP INDEX nip");
                }
            } catch (\Throwable $e) {
                // Index 'nip' tidak ada, lanjut
            }
        }

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->unique(
                ['nip', 'tahun', 'jabatan', 'unit_kerja', 'status_kerja'],
                $indexName
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = 'portofolio_kinerja';
        $indexName = 'portofolio_kinerja_unique_index';

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->dropUnique($indexName);
        });
    }
};
