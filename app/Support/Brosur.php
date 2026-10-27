<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Gambar brosur program (poster promo + lembar voucher).
 *
 * Nilai unggahan disimpan di tabel pengaturan berupa nama file
 * di public/images/brosur. Bila belum pernah diunggah, atau filenya
 * sudah tidak ada di disk (mis. ikut terhapus), tampilkan gambar bawaan.
 */
class Brosur
{
    private const META = [
        'brochure_main' => [
            'title' => 'Poster promo',
            'note' => 'Gambar yang dibagikan ke customer',
            'fallback' => 'images/brosur-poster.jpeg',
        ],
        'brochure_voucher' => [
            'title' => 'Lembar voucher',
            'note' => 'Bentuk voucher cetak yang dibawa customer',
            'fallback' => 'images/brosur-voucher.jpeg',
        ],
    ];

    /**
     * Daftar brosur untuk halaman pengaturan.
     *
     * @return list<array{key: string, title: string, note: string, url: string}>
     */
    public static function semua(): array
    {
        $daftar = [];

        foreach (self::META as $kunci => $meta) {
            $daftar[] = [
                'key' => $kunci,
                'title' => $meta['title'],
                'note' => $meta['note'],
                'url' => self::url($kunci),
            ];
        }

        return $daftar;
    }

    /**
     * URL publik gambar brosur: file unggahan bila masih ada, jika tidak gambar bawaan.
     */
    public static function url(string $kunci): string
    {
        return asset(self::relatif($kunci));
    }

    /**
     * Path relatif (terhadap public) gambar brosur.
     */
    public static function relatif(string $kunci): string
    {
        if (! isset(self::META[$kunci])) {
            throw new InvalidArgumentException("Kunci brosur tidak dikenal: {$kunci}");
        }

        $tersimpan = Pengaturan::ambil($kunci);

        if ($tersimpan && File::exists(self::directory().DIRECTORY_SEPARATOR.$tersimpan)) {
            return 'images/brosur/'.$tersimpan;
        }

        return self::META[$kunci]['fallback'];
    }

    public static function directory(): string
    {
        return public_path('images'.DIRECTORY_SEPARATOR.'brosur');
    }
}
