<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pdrb_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_wilayah');
            $table->string('pendekatan'); // lapangan_usaha / pengeluaran
            $table->boolean('is_locked')->default(false);
            $table->timestamp('lock_deadline')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['id_wilayah', 'pendekatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdrb_locks');
    }
};
