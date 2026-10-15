<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function index(): View
    {
        $year = now()->year;

        $totalCustomers = Customer::count();
        $withBirthDate = Customer::whereNotNull('lahir_tgl')->whereNotNull('lahir_bln')->count();
        $withConsent = Customer::where('setuju_pesan', true)->count();

        $hampersEligible = Customer::query()
            ->whereNotNull('lahir_tgl')
            ->whereNotNull('lahir_bln')
            ->whereDoesntHave('hampers', function (Builder $query) use ($year) {
                $query->where('tahun_kuota', $year);
            })
            ->count();

        $vouchersCreated = Voucher::count();
        $vouchersUsed = Voucher::where('status', 'USED')->count();
        $unusedVouchers = Voucher::where('status', 'AVAILABLE')->count();

        $labels = Voucher::KATEGORI_LABELS;

        $voucherSources = Voucher::query()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->map(function ($count, $code) use ($labels) {
                return [
                    'code' => $code,
                    'label' => $labels[$code] ?? $code,
                    'count' => (int) $count,
                ];
            })
            ->values();

        $latestCustomers = Customer::query()
            ->withCount('kunjungan as visits_count')
            ->withCount(['hampers as claimed_this_year' => function (Builder $query) use ($year) {
                $query->where('tahun_kuota', $year);
            }])
            ->orderByDesc('id_customer')
            ->limit(5)
            ->get();

        return view('dashboard.ang-index', compact(
            'totalCustomers',
            'vouchersCreated',
            'vouchersUsed',
            'hampersEligible',
            'voucherSources',
            'unusedVouchers',
            'withBirthDate',
            'withConsent',
            'latestCustomers',
        ));
    }
}
