<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\KuotaHampers;
use App\Services\EvaluasiHampers;
use App\Support\Pengaturan;
use Illuminate\Contracts\View\View;

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
}
