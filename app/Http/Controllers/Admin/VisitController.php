<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SearchCustomerRequest;
use App\Http\Requests\Visit\StoreVisitRequest;
use App\Models\Customer;
use App\Models\Kunjungan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VisitController extends Controller
{
    public function index(SearchCustomerRequest $request): View
    {
        $keyword = trim((string) $request->input('q'));

        $customers = Customer::query()
            ->withCount('kunjungan as visits_count')
            ->withMax('kunjungan as last_visit_at', 'tgl_kunjungan')
            ->withCount(['kunjungan as visits_90d_count' => function ($query) {
                $query->where('tgl_kunjungan', '>=', now()->subDays(90)->toDateString());
            }])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('no_wa', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('last_visit_at')
            ->orderBy('nama')
            ->get();

        $customerOptions = Customer::query()->orderBy('nama')->get();

        return view('visits.ang-index', compact('customers', 'customerOptions'));
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
