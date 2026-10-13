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
        Schema::create('pesan_ulang_tahun', function (Blueprint $table) {
            $table->increments('id_pesan');
            $table->unsignedInteger('id_customer');
            $table->year('tahun');
            $table->enum('jenis', ['BIASA', 'HAMPERS']);
            $table->enum('status', ['PENDING', 'TERKIRIM', 'GAGAL'])->default('PENDING');
            $table->string('wa_message_id', 100)->nullable();
            $table->string('error', 150)->nullable();
            $table->dateTime('dikirim_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('id_customer')->references('id_customer')->on('customer');
            $table->unique(['id_customer', 'tahun'], 'uq_customer_tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesan_ulang_tahun');
    }
};
