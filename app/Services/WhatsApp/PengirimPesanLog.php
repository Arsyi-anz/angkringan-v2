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
    public function kirim(string $nomor, string $isi, ?string $templateKunci = null, array $params = []): string
    {
        Log::channel('whatsapp')->info('Simulasi kirim WhatsApp', array_filter([
            'nomor' => $nomor,
            'pesan' => $isi,
            'template' => $templateKunci,
            'params' => $params !== [] ? $params : null,
        ], fn ($nilai) => $nilai !== null));

        return 'sim-'.Str::lower(Str::uuid());
    }
}
