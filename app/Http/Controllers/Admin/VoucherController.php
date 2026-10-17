<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\CheckVoucherRequest;
use App\Http\Requests\Voucher\GenerateVoucherRequest;
use App\Models\Voucher;
use App\Support\Pengaturan;
use App\Support\VoucherKuota;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $counts = VoucherKuota::terpakai();

        $channels = collect(VoucherKuota::CHANNELS)
            ->map(fn (string $code) => [
                'code' => $code,
                'name' => Voucher::KATEGORI_LABELS[$code] ?? $code,
                'count' => $counts[$code],
            ])
            ->all();

        return view('vouchers.ang-index', [
            'channels' => $channels,
            'limits' => VoucherKuota::batas(),
            'vouchers' => $this->filter($request)->paginate(15)->withQueryString(),
            'voucherTemplate' => $this->voucherTemplateUrl(),
        ]);
    }

    public function generate(GenerateVoucherRequest $request): RedirectResponse
    {
        $source = strtoupper((string) $request->string('source'));
        $quantity = $request->integer('quantity');

        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $codes[] = $this->kodeBaru($source);
        }

        Voucher::query()->insert(array_map(fn (string $code) => [
            'kode_voucher' => $code,
            'kategori' => $source,
            'status' => 'AVAILABLE',
            // Satu waktu untuk seluruh batch, dipakai modal cetak brosur.
            'created_at' => now(),
        ], $codes));

        return redirect()
            ->route('admin.vouchers.index')
            ->with('status', sprintf(
                '%d kode voucher %s berhasil dibuat.',
                $quantity,
                Voucher::KATEGORI_LABELS[$source] ?? $source,
            ));
    }

    /**
     * Daftar kode untuk modal cetak brosur (dipakai oleh ang-vouchers.js).
     * Batch terakhir = kode dengan waktu pembuatan terbaru.
     */
    public function codes(Request $request): JsonResponse
    {
        $source = strtoupper(trim((string) $request->query('source', 'BRO')));

        if (! in_array($source, VoucherKuota::CHANNELS, true)) {
            $source = 'BRO';
        }

        $rows = Voucher::query()
            ->where('kategori', $source)
            ->where('status', 'AVAILABLE')
            ->orderByDesc('created_at')
            ->orderByDesc('id_voucher')
            ->get(['kode_voucher', 'created_at']);

        $codes = $rows->pluck('kode_voucher')->values();
        $terbaru = $rows->first();
        $latest = $terbaru
            ? $rows->where('created_at', $terbaru->created_at)->pluck('kode_voucher')->values()
            : collect();

        return response()->json([
            'source' => $source,
            'total' => $codes->count(),
            'codes' => $codes,
            'latest' => $latest,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $vouchers = $this->filter($request)->get();
        $filename = 'voucher-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($vouchers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Kode', 'Sumber', 'Status', 'Dibuat', 'Digunakan']);

            foreach ($vouchers as $voucher) {
                fputcsv($handle, [
                    $voucher->kode_voucher,
                    $voucher->source_label,
                    $voucher->is_used ? 'USED' : 'AVAILABLE',
                    $voucher->created_at,
                    $voucher->used_at,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $vouchers = $this->filter($request)->get();

        $pdf = Pdf::loadView('pdf.vouchers', [
            'vouchers' => $vouchers,
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'source' => strtoupper(trim((string) $request->query('source'))),
            ],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('voucher-'.now()->format('Ymd-His').'.pdf');
    }

    public function validatePage(): View
    {
        return $this->renderValidate(session('result'));
    }

    public function check(CheckVoucherRequest $request): RedirectResponse
    {
        $code = $request->input('code');
        $voucher = Voucher::query()->where('kode_voucher', $code)->first();

        return redirect()
            ->route('admin.vouchers.validate')
            ->with('result', [
                'state' => match (true) {
                    $voucher === null => 'invalid',
                    $voucher->is_used => 'used',
                    default => 'valid',
                },
                'code' => $code,
                'voucher' => $voucher,
            ]);
    }

    public function redeem(Voucher $voucher): RedirectResponse
    {
        if ($voucher->is_used) {
            return back()->with('status', 'Voucher '.$voucher->code.' sudah ditandai USED.');
        }

        $voucher->update([
            'status' => 'USED',
            'used_at' => now(),
            'id_admin' => auth('admin')->id(),
        ]);

        return back()->with('status', 'Voucher '.$voucher->code.' ditandai USED.');
    }

    private function renderValidate(?array $result): View
    {
        return view('vouchers.ang-validate', [
            'result' => $result,
            'vouchers' => Voucher::query()
                ->orderByDesc('created_at')
                ->orderByDesc('id_voucher')
                ->limit(10)
                ->get(),
        ]);
    }

    private function filter(Request $request): Builder
    {
        $query = Voucher::query()->orderByDesc('created_at')->orderByDesc('id_voucher');

        $q = strtoupper(trim((string) $request->query('q')));
        if ($q !== '') {
            $query->where('kode_voucher', 'like', '%'.$q.'%');
        }

        $source = strtoupper(trim((string) $request->query('source')));
        if (in_array($source, VoucherKuota::CHANNELS, true)) {
            $query->where('kategori', $source);
        }

        return $query;
    }

    private function kodeBaru(string $source): string
    {
        do {
            $code = $source.'-'.strtoupper(Str::random(6));
        } while (Voucher::query()->where('kode_voucher', $code)->exists());

        return $code;
    }

    private function voucherTemplateUrl(): string
    {
        $tersimpan = Pengaturan::ambil('brochure_voucher');

        return asset($tersimpan ? 'images/brosur/'.$tersimpan : 'images/brosur-voucher.jpeg');
    }
}
