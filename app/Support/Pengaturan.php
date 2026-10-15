<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Pengaturan
{
    public const DEFAULT_MIN_VISITS = 5;

    public const DEFAULT_WINDOW_DAYS = 90;

    /**
     * Cache nilai pengaturan selama satu request/test.
     * Disimpan agar evaluasi massal tidak membaca tabel per model.
     *
     * @var array<string, string|null>
     */
    private static array $cache = [];

    public static function ambil(string $kunci, ?string $default = null): ?string
    {
        if (! array_key_exists($kunci, static::$cache)) {
            static::$cache[$kunci] = DB::table('pengaturan')->where('kunci', $kunci)->value('nilai');
        }

        return static::$cache[$kunci] ?? $default;
    }

    public static function ambilInt(string $kunci, int $default = 0): int
    {
        $nilai = static::ambil($kunci);

        return $nilai === null ? $default : (int) $nilai;
    }

    public static function simpan(string $kunci, string $nilai): void
    {
        DB::table('pengaturan')->updateOrInsert(['kunci' => $kunci], ['nilai' => $nilai]);

        static::$cache[$kunci] = $nilai;
    }

    /**
     * Buang cache (dipanggil tiap aplikasi boot, mis. di tiap test).
     */
    public static function lupakanSemua(): void
    {
        static::$cache = [];
    }
}
