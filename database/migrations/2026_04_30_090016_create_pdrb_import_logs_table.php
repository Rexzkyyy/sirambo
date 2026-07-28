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
        Schema::create('pdrb_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_wilayah')->nullable();
            $table->unsignedBigInteger('id_tahun')->nullable();
            $table->unsignedBigInteger('id_periode')->nullable();
            $table->string('pendekatan', 50)->comment('lapangan_usaha / pengeluaran');
            $table->string('tipe_import', 50)->default('single')->comment('single / multi_tahun');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdrb_import_logs');
    }
};
