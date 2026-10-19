<?php

namespace App\Support;

use App\Console\Commands\SendBirthdayMessages;
use App\Models\Customer;
use Illuminate\Support\Carbon;

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
     *
     * @param  Carbon|null  $batasAmbil  tanggal batas pengambilan hampers
     *                                   (default: BATAS_HARI setelah pesan dikirim)
     */
    public static function layak(Customer $customer, ?Carbon $batasAmbil = null): string
    {
        $batasAmbil = ($batasAmbil ?? now()->addDays(SendBirthdayMessages::BATAS_HARI))
            ->copy()
            ->locale('id')
            ->translatedFormat('d F Y');

        return 'Selamat Ulang Tahun! 🎂'."\n\n"
            .'Hai, '.$customer->name.'! Selamat bertambah usia, ya! 🥳 Semoga selalu sehat, bahagia, dilancarkan rezekinya, dan semua impianmu tercapai.'."\n\n"
            .'Sebagai bentuk terima kasih karena sudah menjadi bagian dari keluarga ANGKRINGAN HOTBER, kami punya sedikit hadiah spesial untukmu! 🎁'."\n\n"
            .'Silakan datang ke outlet ANGKRINGAN HOTBER dan tunjukkan chat WhatsApp ini kepada kasir untuk klaim hampers ulang tahunmu.'."\n\n"
            .'Hampers dapat diambil sampai tanggal '.$batasAmbil.'.'."\n\n"
            .'Semoga hadiah kecil dari kami bisa menambah kebahagiaan di hari istimewamu. Terima kasih sudah menjadi bagian dari perjalanan ANGKRINGAN HOTBER! 🧡'."\n\n"
            ."Salam hangat,\n"
            .'ANGKRINGAN HOTBER';
    }
}
