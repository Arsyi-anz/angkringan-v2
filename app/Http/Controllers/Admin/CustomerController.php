<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SearchCustomerRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(SearchCustomerRequest $request): View
    {
        $customers = $this->customers->all($request->filters());

        return view('customers.ang-index', compact('customers'));
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Customer berhasil disimpan.');
    }

    public function show(Customer $customer): CustomerResource
    {
        $customer->loadCount('kunjungan as visits_count');

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());
        $customer->loadCount('kunjungan as visits_count');

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'Customer berhasil dihapus.']);
    }

    public function exportCsv(SearchCustomerRequest $request): StreamedResponse
    {
        $customers = $this->customers->all($request->filters());
        $filename = 'customer-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Nama', 'No WA', 'Tgl Lahir', 'Bulan Lahir',
                'Setuju Pesan', 'Total Kunjungan', 'Dibuat',
            ]);

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id_customer,
                    $customer->nama,
                    $customer->no_wa,
                    $customer->lahir_tgl,
                    $customer->lahir_bln,
                    $customer->setuju_pesan ? 'Ya' : 'Tidak',
                    $customer->visits_count,
                    optional($customer->created_at)->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(SearchCustomerRequest $request)
    {
        $customers = $this->customers->all($request->filters());

        $pdf = Pdf::loadView('pdf.customers', [
            'customers' => $customers,
            'filters' => $request->filters(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('customer-'.now()->format('Ymd-His').'.pdf');
    }
}
