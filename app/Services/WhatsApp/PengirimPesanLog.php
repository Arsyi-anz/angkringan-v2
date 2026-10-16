<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Driver simulasi: pesan dicatat ke log khusus storage/logs/wa.log.
 * Dipakai selama gateway WhatsApp sungguhan belum tersambung.
 */
class PengirimPesanLog implements PengirimPesan
{
    public function kirim(string $nomor, string $pesan): string
    {
        Log::channel('whatsapp')->info('Simulasi kirim WhatsApp', [
            'nomor' => $nomor,
            'pesan' => $pesan,
        ]);

        return 'sim-'.Str::lower(Str::uuid());
    }
}
