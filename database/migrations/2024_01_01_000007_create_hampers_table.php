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
        Schema::create('hampers', function (Blueprint $table) {
            $table->increments('id_hampers');
            $table->unsignedInteger('id_customer');
            $table->unsignedInteger('id_pesan')->unique();
            $table->year('tahun_kuota');
            $table->string('kode_klaim', 10)->unique();
            $table->date('batas_ambil');
            $table->enum('status', ['BELUM_DIAMBIL', 'DIAMBIL', 'KEDALUWARSA'])->default('BELUM_DIAMBIL');
            $table->dateTime('diambil_at')->nullable();
            $table->unsignedInteger('id_admin')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('id_customer')->references('id_customer')->on('customer');
            $table->foreign('id_pesan')->references('id_pesan')->on('pesan_ulang_tahun');
            $table->foreign('id_admin')->references('id_admin')->on('admin');
            $table->unique(['id_customer', 'tahun_kuota'], 'uq_customer_tahun');
            $table->index(['tahun_kuota', 'status', 'batas_ambil'], 'idx_kuota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hampers');
    }
};
