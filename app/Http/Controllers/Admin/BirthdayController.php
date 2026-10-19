<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\SendBirthdayMessages;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Hampers;
use App\Models\KuotaHampers;
use App\Models\PesanUlangTahun;
use App\Services\EvaluasiHampers;
use App\Support\Pengaturan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class BirthdayController extends Controller
{
    public function __construct(private readonly EvaluasiHampers $evaluasi) {}

    /**
     * Kriteria evaluasi hampers. Nilainya disimpan di halaman Pengaturan Program.
     */
    public function index(): View
    {
        $year = now()->year;
        $windowDays = Pengaturan::ambilInt('window_days', Pengaturan::DEFAULT_WINDOW_DAYS);
        $minVisits = Pengaturan::ambilInt('min_visits', Pengaturan::DEFAULT_MIN_VISITS);

        $quotaYear = KuotaHampers::tahun($year);

        $withBirthDate = Customer::query()
            ->whereNotNull('lahir_tgl')
            ->whereNotNull('lahir_bln')
            ->count();

        // Member yang punya tanggal lahir = masuk daftar evaluasi.
        // visits_window_count : kunjungan window_days hari sebelum ulang tahun masing-masing.
        // hampers_eligible     : memenuhi minimal kunjungan, punya tanggal lahir, dan
        //                        belum menerima hampers di tahun berjalan.
        $members = $this->evaluasi->anggota($year)
            ->sortBy([
                ['lahir_bln', 'asc'],
                ['lahir_tgl', 'asc'],
                ['nama', 'asc'],
            ])
            ->values();

        // Status pesan ulang tahun tahun berjalan per member (null = belum ada pesan).
        $pesanByCustomer = PesanUlangTahun::query()
            ->where('tahun', $year)
            ->get()
            ->keyBy('id_customer');

        foreach ($members as $member) {
            $member->setAttribute('status_pesan', $pesanByCustomer->get($member->id_customer)?->status);
        }

        // Hampers tahun berjalan per member. Yang lewat batas ambil tapi belum diklaim
        // otomatis ditandai KEDALUWARSA.
        $hampersByCustomer = Hampers::query()
            ->where('tahun_kuota', $year)
            ->get()
            ->keyBy('id_customer');

        $kadaluarsa = $hampersByCustomer
            ->filter(fn (Hampers $hampers) => $hampers->status === 'BELUM_DIAMBIL'
                && $hampers->batas_ambil->lt(today()));

        if ($kadaluarsa->isNotEmpty()) {
            Hampers::query()
                ->whereIn('id_hampers', $kadaluarsa->pluck('id_hampers'))
                ->update(['status' => 'KEDALUWARSA']);

            $kadaluarsa->each(fn (Hampers $hampers) => $hampers->status = 'KEDALUWARSA');
        }

        // hampers_tahun : model Hampers tahun berjalan | null
        // hampers_batas : batas klaim (ulang tahun + BATAS_HARI, atau batas dari record)
        foreach ($members as $member) {
            $hampers = $hampersByCustomer->get($member->id_customer);

            $member->setAttribute('hampers_tahun', $hampers);
            $member->setAttribute('hampers_batas', $hampers?->batas_ambil ?? $this->batasKlaim($member));
        }

        $eligibleCount = $members->filter(fn (Customer $customer) => $customer->hampers_eligible)->count();

        return view('birthday.ang-index', compact(
            'quotaYear',
            'eligibleCount',
            'withBirthDate',
            'members',
            'minVisits',
            'windowDays',
        ));
    }

    /**
     * Tandai hampers sudah diambil (KLAIM) oleh member di outlet.
     * Record hampers dibuat saat klaim jika belum ada (mis. tanpa pesan WhatsApp).
     */
    public function klaim(Customer $customer): RedirectResponse
    {
        $hampers = Hampers::query()
            ->where('id_customer', $customer->id_customer)
            ->where('tahun_kuota', now()->year)
            ->first();

        if ($hampers && $hampers->status === 'DIAMBIL') {
            return back()->with('status', 'Hampers untuk '.$customer->nama.' sudah pernah diklaim.');
        }

        $batas = $hampers?->batas_ambil ?? $this->batasKlaim($customer);

        if ($batas === null) {
            return back()->with('status', 'Tanggal lahir '.$customer->nama.' belum lengkap, klaim dibatalkan.');
        }

        if ($batas->lt(today())) {
            if ($hampers && $hampers->status !== 'KEDALUWARSA') {
                $hampers->update(['status' => 'KEDALUWARSA']);
            }

            return back()->with('status', 'Hampers untuk '.$customer->nama.' sudah kadaluwarsa dan tidak dapat diklaim.');
        }

        if ($hampers) {
            $hampers->update([
                'status' => 'DIAMBIL',
                'diambil_at' => now(),
                'id_admin' => auth('admin')->id(),
            ]);
        } else {
            $hampers = Hampers::create([
                'id_customer' => $customer->id_customer,
                'id_pesan' => null,
                'tahun_kuota' => now()->year,
                'kode_klaim' => Hampers::kodeKlaimBaru(),
                'batas_ambil' => $batas->toDateString(),
                'status' => 'DIAMBIL',
                'diambil_at' => now(),
                'id_admin' => auth('admin')->id(),
            ]);
        }

        return back()->with('status', 'Hampers '.$hampers->kode_klaim.' ditandai KLAIM.');
    }

    /**
     * Batas klaim hampers: ulang tahun tahun berjalan + SendBirthdayMessages::BATAS_HARI.
     * null bila tanggal lahir tidak lengkap/keliru.
     */
    private function batasKlaim(Customer $customer): ?Carbon
    {
        if ($customer->lahir_tgl === null || $customer->lahir_bln === null) {
            return null;
        }

        $ulangTahun = Carbon::createSafe(now()->year, (int) $customer->lahir_bln, (int) $customer->lahir_tgl);

        return $ulangTahun?->copy()->addDays(SendBirthdayMessages::BATAS_HARI);
    }
}
