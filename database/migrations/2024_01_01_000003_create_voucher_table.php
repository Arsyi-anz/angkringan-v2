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
        Schema::create('voucher', function (Blueprint $table) {
            $table->increments('id_voucher');
            $table->string('kode_voucher', 10)->unique();
            $table->enum('kategori', ['IG', 'FB', 'TT', 'BRO']);
            $table->enum('status', ['AVAILABLE', 'USED'])->default('AVAILABLE');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('used_at')->nullable();
            $table->unsignedInteger('id_admin')->nullable();

            $table->foreign('id_admin')->references('id_admin')->on('admin');
            $table->index(['kategori', 'status'], 'idx_kategori_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};
