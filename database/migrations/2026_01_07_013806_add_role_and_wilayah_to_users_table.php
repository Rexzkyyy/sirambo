<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['provinsi', 'kabupaten', 'kota'])
                  ->after('password');

            $table->unsignedBigInteger('id_provinsi')
                  ->nullable()
                  ->after('role');

            $table->unsignedBigInteger('id_kabupaten')
                  ->nullable()
                  ->after('id_provinsi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'id_provinsi', 'id_kabupaten']);
        });
    }
};
