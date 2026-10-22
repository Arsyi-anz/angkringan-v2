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
    | App\Support\TemplatePesanUlangTahun dengan placeholder {{1}}, {{2}}.
    |
    */

    'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),

    'templates' => [
        'biasa' => env('WHATSAPP_TEMPLATE_BIASA', 'birthday_biasa'),
        'hampers' => env('WHATSAPP_TEMPLATE_HAMPERS', 'birthday_hampers'),
    ],

];
