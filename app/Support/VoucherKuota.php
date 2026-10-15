<?php

namespace App\Support;

use App\Models\Voucher;

class VoucherKuota
{
    public const CHANNELS = ['IG', 'FB', 'TT', 'BRO'];

    public const LIMIT_BROCHURE_KEY = 'limit_voucher_brochure';

    public const LIMIT_DIGITAL_KEY = 'limit_voucher_digital';

    public const DEFAULT_BROCHURE = 2000;

    public const DEFAULT_DIGITAL = 50;

    /**
     * Batas campaign per kelompok kanal. Brosur berdiri sendiri,
     * digital (IG + FB + TT) berbagi satu kuota.
     *
     * @return array{brochure: int, digital: int}
     */
    public static function batas(): array
    {
        return [
            'brochure' => Pengaturan::ambilInt(self::LIMIT_BROCHURE_KEY, self::DEFAULT_BROCHURE),
            'digital' => Pengaturan::ambilInt(self::LIMIT_DIGITAL_KEY, self::DEFAULT_DIGITAL),
        ];
    }

    /**
     * Jumlah kode yang sudah dibuat per kanal.
     *
     * @return array<string, int>
     */
    public static function terpakai(): array
    {
        $counts = Voucher::query()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        $hasil = [];
        foreach (self::CHANNELS as $code) {
            $hasil[$code] = (int) ($counts[$code] ?? 0);
        }

        return $hasil;
    }

    /**
     * Sisa kuota untuk satu kanal (0 bila sudah habis).
     */
    public static function sisa(string $source): int
    {
        if (! in_array($source, self::CHANNELS, true)) {
            return 0;
        }

        $batas = self::batas();
        $terpakai = self::terpakai();

        if ($source === 'BRO') {
            return max(0, $batas['brochure'] - $terpakai['BRO']);
        }

        $digital = $terpakai['IG'] + $terpakai['FB'] + $terpakai['TT'];

        return max(0, $batas['digital'] - $digital);
    }
}
