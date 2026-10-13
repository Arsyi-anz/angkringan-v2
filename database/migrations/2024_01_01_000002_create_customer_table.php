<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->increments('id_customer');
            $table->string('nama', 50);
            $table->string('no_wa', 15)->unique();
            $table->unsignedTinyInteger('lahir_tgl')->nullable();
            $table->unsignedTinyInteger('lahir_bln')->nullable();
            $table->boolean('setuju_pesan')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->index(['lahir_bln', 'lahir_tgl'], 'idx_lahir');
        });

        DB::statement('ALTER TABLE customer ADD CONSTRAINT chk_customer_lahir_tgl CHECK (lahir_tgl BETWEEN 1 AND 31)');
        DB::statement('ALTER TABLE customer ADD CONSTRAINT chk_customer_lahir_bln CHECK (lahir_bln BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE customer ADD CONSTRAINT chk_customer_lahir_pasangan CHECK ((lahir_tgl IS NULL) = (lahir_bln IS NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
