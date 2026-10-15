<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\KuotaHampers;
use App\Support\Pengaturan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class BirthdayController extends Controller
{
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
        // hampers_eligible : punya tanggal lahir dan belum pernah menerima hampers
        //                    di tahun berjalan (satu dengan metrik "Layak hampers"
        //                    di Dashboard).
        $members = Customer::query()
            ->whereNotNull('lahir_tgl')
            ->whereNotNull('lahir_bln')
            ->withCount('kunjungan as visits_count')
            ->withCount(['kunjungan as visits_window_count' => function (Builder $query) use ($windowDays) {
                $query->where('tgl_kunjungan', '>=', now()->subDays($windowDays)->toDateString());
            }])
            ->withCount(['hampers as claimed_this_year' => function (Builder $query) use ($year) {
                $query->where('tahun_kuota', $year);
            }])
            ->orderBy('lahir_bln')
            ->orderBy('lahir_tgl')
            ->orderBy('nama')
            ->get();

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
