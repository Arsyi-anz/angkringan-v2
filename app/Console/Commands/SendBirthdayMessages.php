<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Hampers;
use App\Models\KuotaHampers;
use App\Models\PesanUlangTahun;
use App\Services\EvaluasiHampers;
use App\Services\WhatsApp\PengirimPesan;
use App\Support\TemplatePesanUlangTahun;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class SendBirthdayMessages extends Command
{
    /**
     * Hampers boleh diambil dalam 14 hari sejak pesan terkirim.
     */
    private const BATAS_HARI = 14;

    protected $signature = 'birthday:send';

    protected $description = 'Mengirim pesan ulang tahun otomatis ke member yang berulang tahun hari ini';

    public function handle(EvaluasiHampers $evaluasi, PengirimPesan $pengirim): int
    {
        $tanggal = today();
        $tahun = (int) $tanggal->year;

        $member = $evaluasi->anggota($tahun)
            ->filter(fn (Customer $customer) => (int) $customer->lahir_tgl === (int) $tanggal->day
                && (int) $customer->lahir_bln === (int) $tanggal->month)
            ->filter(fn (Customer $customer) => $customer->wa_consent);

        $sisaKuota = $this->sisaKuota($tahun);
        $terkirim = $gagal = $dilewati = 0;

        foreach ($member as $customer) {
            $pesan = PesanUlangTahun::query()->firstOrNew([
                'id_customer' => $customer->id_customer,
                'tahun' => $tahun,
            ]);

            if ($pesan->exists && $pesan->status === PesanUlangTahun::STATUS_TERKIRIM) {
                $dilewati++;

                continue;
            }

            $layak = $customer->hampers_eligible && $sisaKuota > 0;
            $jenis = $layak ? PesanUlangTahun::JENIS_HAMPERS : PesanUlangTahun::JENIS_BIASA;
            $isi = $layak
                ? TemplatePesanUlangTahun::layak($customer)
                : TemplatePesanUlangTahun::tidakLayak($customer);

            try {
                $waMessageId = $pengirim->kirim($customer->whatsapp, $isi);

                $pesan->fill([
                    'jenis' => $jenis,
                    'status' => PesanUlangTahun::STATUS_TERKIRIM,
                    'wa_message_id' => $waMessageId,
                    'error' => null,
                    'dikirim_at' => now(),
                ])->save();

                if ($layak) {
                    $this->buatHampers($customer, $pesan, $tahun);
                    $sisaKuota--;
                }

                $terkirim++;
                $this->line(sprintf('  %s -> %s', $customer->name, $jenis));
            } catch (Throwable $e) {
                $pesan->fill([
                    'jenis' => $jenis,
                    'status' => PesanUlangTahun::STATUS_GAGAL,
                    'error' => Str::limit($e->getMessage(), 150),
                    'dikirim_at' => now(),
                ])->save();

                $gagal++;
                $this->warn(sprintf('  %s -> GAGAL (%s)', $customer->name, $e->getMessage()));
            }
        }

        $this->info(sprintf(
            'Pesan ulang tahun %s: %d terkirim, %d gagal, %d dilewati.',
            $tanggal->toFormattedDateString(),
            $terkirim,
            $gagal,
            $dilewati,
        ));

        return self::SUCCESS;
    }

    private function buatHampers(Customer $customer, PesanUlangTahun $pesan, int $tahun): void
    {
        Hampers::create([
            'id_customer' => $customer->id_customer,
            'id_pesan' => $pesan->id_pesan,
            'tahun_kuota' => $tahun,
            'kode_klaim' => $this->kodeKlaim(),
            'batas_ambil' => now()->addDays(self::BATAS_HARI)->toDateString(),
            'status' => 'BELUM_DIAMBIL',
        ]);
    }

    private function kodeKlaim(): string
    {
        do {
            $kode = 'HAM-'.strtoupper(Str::random(6));
        } while (Hampers::query()->where('kode_klaim', $kode)->exists());

        return $kode;
    }

    private function sisaKuota(int $tahun): int
    {
        $terpakai = Hampers::query()->where('tahun_kuota', $tahun)->count();

        return max(0, KuotaHampers::tahun($tahun) - $terpakai);
    }
}
