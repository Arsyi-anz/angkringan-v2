<?php

namespace App\Support;

use App\Models\Customer;

/**
 * Isi pesan WhatsApp ulang tahun.
 * Teksnya sama dengan pratinjau di halaman Birthday (partials.ang-message-modal).
 */
class TemplatePesanUlangTahun
{
    /**
     * Pesan untuk customer yang belum memenuhi syarat hampers.
     */
    public static function tidakLayak(Customer $customer): string
    {
        return 'Selamat Ulang Tahun! 🎂'."\n\n"
            .'Hai, '.$customer->name.'! Selamat bertambah usia, ya! 🥳 Semoga selalu sehat, bahagia, dilancarkan rezekinya, dan semua harapan baikmu segera terwujud.'."\n\n"
            .'Terima kasih sudah menjadi bagian dari keluarga ANGKRINGAN HOTBER. Semoga hari spesialmu dipenuhi kebahagiaan dan hal-hal baik! 🧡'."\n\n"
            ."Salam hangat,\n"
            .'ANGKRINGAN HOTBER';
    }

    /**
     * Pesan untuk customer yang layak dan kuota hampers tersedia.
     */
    public static function layak(Customer $customer): string
    {
        return 'Selamat Ulang Tahun! 🎂'."\n\n"
            .'Hai, '.$customer->name.'! Selamat bertambah usia, ya! 🥳 Semoga selalu sehat, bahagia, dilancarkan rezekinya, dan semua impianmu tercapai.'."\n\n"
            .'Sebagai bentuk terima kasih karena sudah menjadi bagian dari keluarga ANGKRINGAN HOTBER, kami punya sedikit hadiah spesial untukmu! 🎁'."\n\n"
            .'Silakan datang ke outlet ANGKRINGAN HOTBER dan tunjukkan chat WhatsApp ini kepada kasir untuk klaim hampers ulang tahunmu.'."\n\n"
            .'Semoga hadiah kecil dari kami bisa menambah kebahagiaan di hari istimewamu. Terima kasih sudah menjadi bagian dari perjalanan ANGKRINGAN HOTBER! 🧡'."\n\n"
            ."Salam hangat,\n"
            .'ANGKRINGAN HOTBER';
    }
}
