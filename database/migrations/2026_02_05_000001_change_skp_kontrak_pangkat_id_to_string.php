<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change pegawai_pangkat_id and penilai_pangkat_id from integer to string
     * so they can store pangkat codes (e.g. "IX") from portofolio/SIMPEG.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE skp_kontrak MODIFY pegawai_pangkat_id VARCHAR(50) NULL');
            DB::statement('ALTER TABLE skp_kontrak MODIFY penilai_pangkat_id VARCHAR(50) NULL');
        } else {
            Schema::table('skp_kontrak', function (Blueprint $table) {
                $table->string('pegawai_pangkat_id', 50)->nullable()->change();
                $table->string('penilai_pangkat_id', 50)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE skp_kontrak MODIFY pegawai_pangkat_id INT UNSIGNED NULL');
            DB::statement('ALTER TABLE skp_kontrak MODIFY penilai_pangkat_id INT UNSIGNED NULL');
        } else {
            Schema::table('skp_kontrak', function (Blueprint $table) {
                $table->unsignedInteger('pegawai_pangkat_id')->nullable()->change();
                $table->unsignedInteger('penilai_pangkat_id')->nullable()->change();
            });
        }
    }
};
