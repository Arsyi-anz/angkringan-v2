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
        Schema::create('kunjungan', function (Blueprint $table) {
            $table->increments('id_kunjungan');
            $table->unsignedInteger('id_customer');
            $table->date('tgl_kunjungan');
            $table->unsignedInteger('id_admin');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('id_customer')->references('id_customer')->on('customer');
            $table->foreign('id_admin')->references('id_admin')->on('admin');
            $table->unique(['id_customer', 'tgl_kunjungan'], 'uq_customer_tgl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungan');
    }
};
