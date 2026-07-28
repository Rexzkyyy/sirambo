<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lembar_kerja', function (Blueprint $table) {
            // Drop existing index if it exists
            // We use raw SQL to drop index to avoid Laravel's potential naming issues if it doesn't exist
            $indexName = 'lk_unique_version';
            $indexes = DB::select("SHOW INDEX FROM lembar_kerja WHERE Key_name = '$indexName'");
            
            if (count($indexes) > 0) {
                $table->dropUnique($indexName);
            }

            // Create new unique index that includes kategori and sub_kategori
            // This allows one record per category/subcategory per period
            $table->unique(
                ['wilayah_id', 'id_tahun', 'id_periode', 'jenis', 'id_kategori', 'id_sub_kategori'], 
                'lk_unique_version'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_kerja', function (Blueprint $table) {
            $table->dropUnique('lk_unique_version');
            
            // Restore to a simpler version if needed, but usually we just want the correct one above
            $table->unique(['wilayah_id', 'id_tahun', 'id_periode', 'jenis'], 'lk_unique_version');
        });
    }
};
