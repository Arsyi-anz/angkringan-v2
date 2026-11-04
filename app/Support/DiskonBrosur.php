<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Diskon voucher brosur berasal dari posisi slot cetak pada lembar brosur
 * (3 kode per lembar): kode ke-1 = Diskon 50%, ke-2 = Diskon 30%, ke-3 = Diskon 15%,
 * berulang pada urutan seluruh kode brosur dari yang terlama ke terbaru.
 */
class DiskonBrosur
{
    public const KODE_PER_LEMBAR = 3;

    /** pilihan filter => label */
    public const PILIHAN = [
        '50' => 'Diskon 50%',
        '30' => 'Diskon 30%',
        '15' => 'Diskon 15%',
    ];

    /**
     * Pilihan "diskon" dari query string menjadi index slot (0 = 50%, 1 = 30%, 2 = 15%).
     * null bila nilai tidak dikenal (artinya tidak memfilter diskon).
     */
    public static function slot(?string $nilai): ?int
    {
        return match ($nilai) {
            '50' => 0,
            '30' => 1,
            '15' => 2,
            default => null,
        };
    }

    /**
     * Id seluruh voucher brosur pada posisi slot tertentu.
     * Posisi dihitung dengan ROW_NUMBER atas seluruh kode BRO (terlama dulu),
     * membentuk siklus {0, 1, 2} yang cocok dengan urutan slot lembar cetak.
     */
    public static function kodeSlot(int $slot): Builder
    {
        return DB::query()
            ->fromRaw(
                '(SELECT id_voucher, MOD(ROW_NUMBER() OVER (ORDER BY created_at ASC, id_voucher ASC) - 1, 3) AS slot FROM voucher WHERE kategori = \'BRO\') AS ranked'
            )
            ->select('id_voucher')
            ->where('slot', $slot);
    }
}
