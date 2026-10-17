<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SearchCustomerRequest;
use App\Http\Requests\Visit\StoreVisitRequest;
use App\Models\Customer;
use App\Models\Kunjungan;
use App\Services\EvaluasiHampers;
use App\Support\Pengaturan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VisitController extends Controller
{
    public function index(SearchCustomerRequest $request): View
    {
        $keyword = trim((string) $request->input('q'));
        $sort = $request->input('sort');
        $windowDays = Pengaturan::ambilInt('window_days', Pengaturan::DEFAULT_WINDOW_DAYS);

        $customers = Customer::query()
            ->withCount('kunjungan as visits_count')
            ->withMax('kunjungan as last_visit_at', 'tgl_kunjungan')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('no_wa', 'like', "%{$keyword}%");
                });
            })
            ->when($sort === 'visits_desc', function ($query) {
                $query->orderByDesc('visits_count')->orderBy('nama');
            })
            ->when($sort === 'visits_asc', function ($query) {
                $query->orderBy('visits_count')->orderBy('nama');
            })
            ->when(! in_array($sort, ['visits_asc', 'visits_desc'], true), function ($query) {
                $query->orderByDesc('last_visit_at')->orderBy('nama');
            })
            ->get();

        // Isi visits_window_count: kunjungan window_days hari sebelum ulang tahun masing-masing.
        app(EvaluasiHampers::class)->tandai($customers, $windowDays);

        $customerOptions = Customer::query()->orderBy('nama')->get();

        return view('visits.ang-index', compact('customers', 'customerOptions', 'windowDays'));
    }

    public function store(StoreVisitRequest $request): RedirectResponse
    {
        Kunjungan::create([
            'id_customer' => $request->integer('customer_id'),
            'tgl_kunjungan' => $request->input('visited_at'),
            'id_admin' => auth('admin')->id(),
        ]);

        return redirect()
            ->back()
            ->with('status', 'Kunjungan dine-in berhasil dicatat.');
    }
}
