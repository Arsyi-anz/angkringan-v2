<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Klaim hampers bisa dilakukan langsung di outlet tanpa pesan WhatsApp,
     * jadi id_pesan tidak lagi wajib diisi.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `hampers` MODIFY `id_pesan` INT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `hampers` MODIFY `id_pesan` INT UNSIGNED NOT NULL');
    }
};
