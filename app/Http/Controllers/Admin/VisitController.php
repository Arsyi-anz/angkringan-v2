<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visit\StoreVisitRequest;
use App\Models\Kunjungan;
use Illuminate\Http\RedirectResponse;

class VisitController extends Controller
{
    public function store(StoreVisitRequest $request): RedirectResponse
    {
        Kunjungan::create([
            'id_customer' => $request->integer('customer_id'),
            'tgl_kunjungan' => $request->input('visited_at'),
            'id_admin' => auth('admin')->id(),
        ]);

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Kunjungan dine-in berhasil dicatat.');
    }
}
