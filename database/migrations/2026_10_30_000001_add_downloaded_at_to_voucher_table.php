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
        Schema::table('voucher', function (Blueprint $table) {
            $table->dateTime('downloaded_at')->nullable()->after('used_at');
            $table->index(['kategori', 'downloaded_at'], 'idx_kategori_downloaded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher', function (Blueprint $table) {
            $table->dropIndex('idx_kategori_downloaded');
            $table->dropColumn('downloaded_at');
        });
    }
};
