<?php

namespace App\Services\WhatsApp;

interface PengirimPesan
{
    /**
     * Kirim satu pesan teks dan kembalikan id pesan dari gateway.
     *
     * @throws \RuntimeException bila pengiriman gagal
     */
    public function kirim(string $nomor, string $pesan): string;
}
