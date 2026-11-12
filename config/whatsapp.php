<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver WhatsApp
    |--------------------------------------------------------------------------
    |
    | "log"   -> simulasi, pesan dicatat ke storage/logs/wa.log (aman untuk
    |            pengembangan dan menjalankan seluruh fitur tanpa kredensial).
    | "cloud" -> Meta WhatsApp Cloud API (graph.facebook.com).
    |
    */

    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'token' => env('WHATSAPP_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v23.0'),

    /*
    |--------------------------------------------------------------------------
    | Template pesan ulang tahun
    |--------------------------------------------------------------------------
    |
    | Nama template yang sudah di-approve Meta beserta bahasanya. Kunci
    | ("biasa"/"hampers") dipetakan oleh driver cloud; teksnya meniru
    | App\Support\TemplatePesanUlangTahun dengan parameter bernama
    | ({{nama}}, {{tanggal}} — parameter_format NAMED di Meta).
    |
    */

    'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),

    'templates' => [
        'biasa' => env('WHATSAPP_TEMPLATE_BIASA', 'birthday_biasa'),
        'hampers' => env('WHATSAPP_TEMPLATE_HAMPERS', 'birthday_hampers'),
    ],

    /*
    | Nama parameter template per kunci, sesuai urutan nilai yang dikirim
    | oleh SendBirthdayMessages. Dipakai driver cloud untuk field
    | parameter_name; biarkan kosong untuk template positional {{1}}, {{2}}.
    */

    'parameter_names' => [
        'biasa' => ['nama'],
        'hampers' => ['nama', 'tanggal'],
    ],

];
