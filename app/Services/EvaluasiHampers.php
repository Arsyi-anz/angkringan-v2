<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Kunjungan;
use App\Support\Pengaturan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Evaluasi kelayakan hampers.
 *
 * Jendela kunjungan: window_days hari yang berakhir tepat di hari ulang tahun
 * masing-masing member (bukan window_days hari ke belakang dari hari ini).
 * Kunjungan setelah hari ulang tahun tidak dihitung.
 */
class EvaluasiHampers
{
    /**
     * @return Collection<int, Customer> member dengan tanggal lahir, sudah diberi
     *                                   visits_window_count dan claimed_this_year
     */
    public function anggota(?int $tahun = null): Collection
    {
        $tahun = $tahun ?? now()->year;

        $customers = Customer::query()
            ->whereNotNull('lahir_tgl')
            ->whereNotNull('lahir_bln')
            ->withCount(['hampers as claimed_this_year' => function (Builder $query) use ($tahun) {
                $query->where('tahun_kuota', $tahun);
            }])
            ->get();

        return $this->tandai($customers);
    }

    /**
     * Isi visits_window_count pada tiap member agar pembacaan massal tidak query per model.
     *
     * @param  iterable<Customer>  $customers
     * @return Collection<int, Customer>
     */
    public function tandai(iterable $customers, ?int $windowDays = null): Collection
    {
        $windowDays = $windowDays ?? Pengaturan::ambilInt('window_days', Pengaturan::DEFAULT_WINDOW_DAYS);
        $kunjungan = $this->kunjunganPerCustomer($windowDays);

        foreach ($customers as $customer) {
            $customer->setAttribute('visits_window_count', $this->hitungJendela($customer, $kunjungan));
        }

        return collect($customers);
    }

    public function hitungLayak(): int
    {
        return $this->anggota()
            ->filter(fn (Customer $customer) => $customer->hampers_eligible)
            ->count();
    }

    /**
     * Jendela [mulai, akhir] kunjungan yang dihitung untuk satu member.
     * Tanggal ulang tahun dipasang di tahun berjalan (birth_date memakai tahun dummy).
     *
     * @return array{0: string, 1: string}|null null bila tanggal lahir belum/keliru
     */
    public function jendela(Customer $customer, ?int $windowDays = null): ?array
    {
        if ($customer->lahir_tgl === null || $customer->lahir_bln === null) {
            return null;
        }

        $ulangTahun = Carbon::createSafe(now()->year, (int) $customer->lahir_bln, (int) $customer->lahir_tgl);

        if (! $ulangTahun instanceof Carbon) {
            return null;
        }

        $windowDays = $windowDays ?? Pengaturan::ambilInt('window_days', Pengaturan::DEFAULT_WINDOW_DAYS);

        $mulai = $ulangTahun->copy()->subDays($windowDays);
        // Untuk ulang tahun yang belum tiba, jendela dipotong sampai hari ini.
        $akhir = $ulangTahun->lessThan(today()) ? $ulangTahun->copy() : today()->copy();

        return [$mulai->toDateString(), $akhir->toDateString()];
    }

    public function kunjunganJendela(Customer $customer): int
    {
        $jendela = $this->jendela($customer);

        if ($jendela === null) {
            return 0;
        }

        return Kunjungan::query()
            ->whereBetween('tgl_kunjungan', $jendela)
            ->count();
    }

    /**
     * @return Collection<int, Collection<int, string>> id_customer => daftar tanggal kunjungan
     */
    private function kunjunganPerCustomer(int $windowDays): Collection
    {
        // Jendela terlebar mungkin berangkat dari 1 Januari, jadi ambil dari sebelum itu.
        $cutoff = now()->startOfYear()->subDays($windowDays)->toDateString();

        return Kunjungan::query()
            ->where('tgl_kunjungan', '>=', $cutoff)
            ->orderBy('tgl_kunjungan')
            ->get(['id_customer', 'tgl_kunjungan'])
            ->groupBy('id_customer')
            ->map(fn (Collection $rows) => $rows
                ->map(fn (Kunjungan $kunjungan) => $kunjungan->tgl_kunjungan->toDateString())
                ->values());
    }

    /**
     * @param  Collection<int, Collection<int, string>>  $kunjungan
     */
    private function hitungJendela(Customer $customer, Collection $kunjungan): int
    {
        $jendela = $this->jendela($customer);

        if ($jendela === null) {
            return 0;
        }

        [$mulai, $akhir] = $jendela;

        return ($kunjungan->get($customer->id_customer) ?? collect())
            ->filter(fn (string $tgl) => $tgl >= $mulai && $tgl <= $akhir)
            ->count();
    }
}
