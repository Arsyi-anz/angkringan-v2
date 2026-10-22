<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Driver Meta WhatsApp Cloud API (graph.facebook.com).
 *
 * Pesan proaktif wajib berupa template yang sudah di-approve Meta, sehingga
 * parameter templateKunci diterjemahkan menjadi nama template dari
 * config('whatsapp.templates') beserta urutan parameter {{1}}, {{2}}, ...
 * Bila templateKunci null, pesan dikirim sebagai teks bebas (hanya sah di
 * dalam jendela layanan 24 jam).
 */
class PengirimPesanCloudApi implements PengirimPesan
{
    public function kirim(string $nomor, string $isi, ?string $templateKunci = null, array $params = []): string
    {
        $payload = $templateKunci === null
            ? [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $nomor,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $isi,
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $nomor,
                'type' => 'template',
                'template' => [
                    'name' => $this->namaTemplate($templateKunci),
                    'language' => [
                        'code' => config('whatsapp.language', 'id'),
                    ],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(
                            fn (string $nilai) => ['type' => 'text', 'text' => $nilai],
                            array_values($params),
                        ),
                    ]],
                ],
            ];

        $respons = Http::withToken($this->token())
            ->acceptJson()
            ->post($this->endpoint(), $payload);

        $id = $respons->json('messages.0.id');

        if (! $respons->successful() || blank($id)) {
            throw new RuntimeException(trim(sprintf(
                'Cloud API HTTP %d: %s',
                $respons->status(),
                $respons->json('error.message') ?? Str::limit($respons->body(), 200),
            )));
        }

        return $id;
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            trim((string) config('whatsapp.api_version'), '/'),
            trim((string) $this->phoneNumberId(), '/'),
        );
    }

    private function namaTemplate(string $kunci): string
    {
        $nama = config("whatsapp.templates.$kunci");

        if (! filled($nama)) {
            throw new RuntimeException("Nama template WhatsApp belum dikonfigurasi (whatsapp.templates.$kunci).");
        }

        return (string) $nama;
    }

    private function token(): string
    {
        $token = config('whatsapp.token');

        if (! filled($token)) {
            throw new RuntimeException('WHATSAPP_TOKEN belum diisi di .env.');
        }

        return (string) $token;
    }

    private function phoneNumberId(): string
    {
        $id = config('whatsapp.phone_number_id');

        if (! filled($id)) {
            throw new RuntimeException('WHATSAPP_PHONE_NUMBER_ID belum diisi di .env.');
        }

        return (string) $id;
    }
}
