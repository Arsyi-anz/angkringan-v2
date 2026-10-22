<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Hampers;
use App\Models\KuotaHampers;
use App\Models\PesanUlangTahun;
use App\Services\EvaluasiHampers;
use App\Services\WhatsApp\PengirimPesan;
use App\Support\Pengaturan;
use App\Support\TemplatePesanUlangTahun;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class SendBirthdayMessages extends Command
{
    /**
     * Hampers boleh diambil dalam 7 hari sejak pesan terkirim.
     */
    public const BATAS_HARI = 7;

    protected $signature = 'birthday:send
        {--date= : Tanggal ulang tahun yang disimulasikan, format YYYY-MM-DD (wajib bersama --dry-run)}
        {--dry-run : Tampilkan rencana kirim tanpa mengirim pesan atau menulis data}';

    protected $description = 'Mengirim pesan ulang tahun otomatis ke member yang berulang tahun pada tanggal berjalan';

    private bool $waktuDisetelUlang = false;

    public function handle(EvaluasiHampers $evaluasi, PengirimPesan $pengirim): int
    {
        if (! $this->siapkanTanggal()) {
            return self::FAILURE;
        }

        try {
            $tanggal = today();
            $tahun = (int) $tanggal->year;

            $anggota = $evaluasi->anggota($tahun)
                ->filter(fn (Customer $customer) => (int) $customer->lahir_tgl === (int) $tanggal->day
                    && (int) $customer->lahir_bln === (int) $tanggal->month);

            if ($this->option('dry-run')) {
                $this->cetakRencana($anggota, $tanggal);

                return self::SUCCESS;
            }

            $sisaKuota = $this->sisaKuota($tahun);
            $terkirim = $gagal = $dilewati = 0;

            foreach ($anggota->filter(fn (Customer $customer) => $customer->wa_consent) as $customer) {
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
                $batasAmbil = now()->addDays(self::BATAS_HARI);
                $isi = $layak
                    ? TemplatePesanUlangTahun::layak($customer, $batasAmbil)
                    : TemplatePesanUlangTahun::tidakLayak($customer);
                $templateKunci = $layak ? 'hampers' : 'biasa';
                $params = $layak
                    ? [
                        $customer->name,
                        $batasAmbil->copy()->locale('id')->translatedFormat('d F Y'),
                    ]
                    : [$customer->name];

                try {
                    $waMessageId = $pengirim->kirim($customer->whatsapp, $isi, $templateKunci, $params);

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
                        'error' => Str::limit($e->getMessage(), 147),
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
        } finally {
            if ($this->waktuDisetelUlang) {
                Carbon::setTestNow();
            }
        }
    }

    /**
     * Validasi --date dan pasang hari virtual (hanya untuk simulasi).
     */
    private function siapkanTanggal(): bool
    {
        $tanggalOpsi = $this->option('date');

        if (! filled($tanggalOpsi)) {
            return true;
        }

        if (! $this->option('dry-run')) {
            $this->error('Opsi --date hanya boleh dipakai bersama --dry-run (simulasi tidak menulis data).');

            return false;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $tanggalOpsi);
        } catch (Throwable) {
            $parsed = null;
        }

        if (! $parsed instanceof Carbon || $parsed->format('Y-m-d') !== $tanggalOpsi) {
            $this->error('Format --date harus YYYY-MM-DD, contoh: 2026-12-01.');

            return false;
        }

        Carbon::setTestNow($parsed->copy()->startOfDay());
        $this->waktuDisetelUlang = true;

        return true;
    }

    /**
     * @param  Collection<int, Customer>  $anggota
     */
    private function cetakRencana(Collection $anggota, Carbon $tanggal): void
    {
        $minVisits = Pengaturan::ambilInt('min_visits', Pengaturan::DEFAULT_MIN_VISITS);
        $sisaKuota = $this->sisaKuota((int) $tanggal->year);

        $this->info(sprintf(
            'Simulasi kirim pesan ulang tahun %s (tidak ada pesan dikirim, tidak ada data ditulis).',
            $tanggal->toFormattedDateString(),
        ));

        if ($anggota->isEmpty()) {
            $this->line('Tidak ada member yang berulang tahun pada tanggal ini.');

            return;
        }

        $baris = $anggota->map(fn (Customer $customer) => [
            'nama' => $customer->name,
            'wa' => $customer->whatsapp ?: '-',
            'kunjungan' => sprintf('%d/%d', $customer->visits_window_count, $minVisits),
            'keputusan' => $this->putusan($customer, $minVisits, $sisaKuota),
        ])->values();

        $this->table(
            ['Nama', 'WhatsApp', 'Kunjungan', 'Keputusan'],
            $baris->map(fn (array $row) => [
                $row['nama'],
                $row['wa'],
                $row['kunjungan'],
                $row['keputusan'],
            ])->all()
        );

        $dihitung = $baris->countBy(fn (array $row) => Str::before($row['keputusan'], ' '));

        $this->info(sprintf(
            'Simulasi selesai: %d HAMPERS, %d ucapan saja, %d dilewati.',
            $dihitung->get('HAMPERS', 0),
            $dihitung->get('UCAPAN', 0),
            $dihitung->get('DILEWATI', 0),
        ));
    }

    private function putusan(Customer $customer, int $minVisits, int $sisaKuota): string
    {
        if (! $customer->wa_consent) {
            return 'DILEWATI - tanpa consent WA';
        }

        $sudahTerkirim = PesanUlangTahun::query()
            ->where('id_customer', $customer->id_customer)
            ->where('tahun', now()->year)
            ->where('status', PesanUlangTahun::STATUS_TERKIRIM)
            ->exists();

        if ($sudahTerkirim) {
            return 'DILEWATI - pesan sudah terkirim tahun ini';
        }

        if ($customer->hampers_eligible) {
            return $sisaKuota > 0
                ? 'HAMPERS - memenuhi syarat, kode klaim akan dibuat'
                : 'UCAPAN - kuota hampers habis';
        }

        if ($customer->visits_window_count < $minVisits) {
            return sprintf('UCAPAN - kunjungan %d/%d belum mencukupi', $customer->visits_window_count, $minVisits);
        }

        return 'UCAPAN - sudah pernah menerima hampers tahun ini';
    }

    private function buatHampers(Customer $customer, PesanUlangTahun $pesan, int $tahun): void
    {
        Hampers::create([
            'id_customer' => $customer->id_customer,
            'id_pesan' => $pesan->id_pesan,
            'tahun_kuota' => $tahun,
            'kode_klaim' => Hampers::kodeKlaimBaru(),
            'batas_ambil' => now()->addDays(self::BATAS_HARI)->toDateString(),
            'status' => 'BELUM_DIAMBIL',
        ]);
    }

    private function sisaKuota(int $tahun): int
    {
        $terpakai = Hampers::query()->where('tahun_kuota', $tahun)->count();

        return max(0, KuotaHampers::tahun($tahun) - $terpakai);
    }
}
