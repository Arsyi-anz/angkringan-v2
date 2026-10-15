<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\KuotaHampers;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class BirthdayController extends Controller
{
    /**
     * Kriteria evaluasi hampers (sebaiknya diambil dari Pengaturan Program
     * ketika halaman itu sudah ada).
     */
    private const MIN_VISITS = 5;

    private const WINDOW_DAYS = 90;

    public function index(): View
    {
        $year = now()->year;
        $windowDays = self::WINDOW_DAYS;
        $minVisits = self::MIN_VISITS;

        $kuota = KuotaHampers::query()->where('tahun', $year)->value('kuota_maks');
        $quotaYear = $kuota === null ? 100 : (int) $kuota;

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
