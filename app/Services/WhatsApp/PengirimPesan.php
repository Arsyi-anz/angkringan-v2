<?php

namespace App\Services\WhatsApp;

interface PengirimPesan
{
    /**
     * Kirim satu pesan dan kembalikan id pesan dari gateway.
     *
     * @param  string  $nomor  nomor dalam format internasional 628xxxxxxxxxx
     * @param  string  $isi  teks final pesan (dipakai driver log dan fallback
     *                       gateway teks bebas)
     * @param  string|null  $templateKunci  kunci template ('biasa'/'hampers') yang
     *                                      dipetakan ke nama template oleh driver;
     *                                      null = kirim sebagai teks bebas
     * @param  array<int, string>  $params  urutan nilai placeholder {{1}}, {{2}}, ...
     *
     * @throws \RuntimeException bila pengiriman gagal
     */
    public function kirim(string $nomor, string $isi, ?string $templateKunci = null, array $params = []): string;
}
