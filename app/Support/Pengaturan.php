<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Pengaturan
{
    public const DEFAULT_MIN_VISITS = 5;

    public const DEFAULT_WINDOW_DAYS = 90;

    public static function ambil(string $kunci, ?string $default = null): ?string
    {
        $nilai = DB::table('pengaturan')->where('kunci', $kunci)->value('nilai');

        return $nilai ?? $default;
    }

    public static function ambilInt(string $kunci, int $default = 0): int
    {
        $nilai = static::ambil($kunci);

        return $nilai === null ? $default : (int) $nilai;
    }

    public static function simpan(string $kunci, string $nilai): void
    {
        DB::table('pengaturan')->updateOrInsert(['kunci' => $kunci], ['nilai' => $nilai]);
    }
}
