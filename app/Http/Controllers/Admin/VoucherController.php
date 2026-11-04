<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\CheckVoucherRequest;
use App\Http\Requests\Voucher\GenerateVoucherRequest;
use App\Models\Voucher;
use App\Support\Brosur;
use App\Support\DiskonBrosur;
use App\Support\VoucherKuota;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'diskonPilihan' => DiskonBrosur::PILIHAN,
            'penggunaanBrosur' => $this->penggunaanBrosur($request),
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
      * Kode yang sudah pernah diunduh (downloaded_at terisi) tidak disertakan
      * pada codes/latest, tetapi tetap muncul pada all untuk penomoran rentang custom.
      * Semua daftar diurutkan terlama dulu agar urutan slot lembar (50% / 30% / 15%)
      * konsisten dengan penomoran "kode ke-N" di rentang custom.
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
            ->whereNull('downloaded_at')
            ->orderBy('created_at')
            ->orderBy('id_voucher')
            ->get(['kode_voucher', 'created_at']);

        $codes = $rows->pluck('kode_voucher')->values();
        $waktuTerbaru = $rows->max('created_at');
        $latest = $waktuTerbaru
            ? $rows->where('created_at', $waktuTerbaru)->pluck('kode_voucher')->values()
            : collect();

        // Seluruh kode kanal (termasuk yang sudah terunduh), paling lama dulu.
        // Dipakai sebagai nomor rentang custom supaya penomoran tidak bergeser.
        $semua = Voucher::query()
            ->where('kategori', $source)
            ->orderBy('created_at')
            ->orderBy('id_voucher')
            ->get(['kode_voucher', 'downloaded_at']);

        $all = $semua->map(fn (Voucher $voucher) => [
            'code' => $voucher->kode_voucher,
            'downloaded' => $voucher->downloaded_at !== null,
        ])->values();

        $sudahDiunduh = (int) $semua->filter(fn (Voucher $voucher) => $voucher->downloaded_at !== null)->count();

        return response()->json([
            'source' => $source,
            'total' => $codes->count(),
            'total_all' => $all->count(),
            'downloaded' => $sudahDiunduh,
            'codes' => $codes,
            'latest' => $latest,
            'all' => $all,
        ]);
    }

    /**
     * Tandai kode sebagai sudah diunduh (dipanggil modal cetak setelah ZIP berhasil).
     * Hanya kode AVAILABLE milik kanal terkait yang belum pernah ditandai.
     */
    public function markDownloaded(Request $request): JsonResponse
    {
        $source = strtoupper(trim((string) $request->input('source', 'BRO')));

        if (! in_array($source, VoucherKuota::CHANNELS, true)) {
            $source = 'BRO';
        }

        $request->validate([
            'codes' => ['required', 'array', 'max:6000'],
            'codes.*' => ['string', 'max:10', 'distinct'],
        ]);

        $codes = array_values(array_unique(array_map(
            fn (string $kode) => strtoupper(trim($kode)),
            $request->input('codes')
        )));

        $marked = Voucher::query()
            ->where('kategori', $source)
            ->where('status', 'AVAILABLE')
            ->whereNull('downloaded_at')
            ->whereIn('kode_voucher', $codes)
            ->update(['downloaded_at' => now()]);

        return response()->json([
            'source' => $source,
            'requested' => count($codes),
            'marked' => $marked,
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
                'status' => strtoupper(trim((string) $request->query('status'))),
                'diskon' => DiskonBrosur::PILIHAN[(string) $request->query('diskon')] ?? null,
                'periode' => $this->labelPeriode($request),
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

        $this->terapkanPencarian($query, $request);
        $this->terapkanSumber($query, $request);
        $this->terapkanStatus($query, $request);
        $this->terapkanDiskon($query, $request);
        $this->terapkanPeriode($query, $request);

        return $query;
    }

    private function terapkanPencarian(Builder $query, Request $request): void
    {
        $q = strtoupper(trim((string) $request->query('q')));
        if ($q !== '') {
            $query->where('kode_voucher', 'like', '%'.$q.'%');
        }
    }

    private function terapkanSumber(Builder $query, Request $request): void
    {
        $source = strtoupper(trim((string) $request->query('source')));
        if (in_array($source, VoucherKuota::CHANNELS, true)) {
            $query->where('kategori', $source);
        }
    }

    private function terapkanStatus(Builder $query, Request $request): void
    {
        $status = strtoupper(trim((string) $request->query('status')));
        if (in_array($status, ['AVAILABLE', 'USED'], true)) {
            $query->where('status', $status);
        }
    }

    private function terapkanDiskon(Builder $query, Request $request): void
    {
        $slot = DiskonBrosur::slot($request->query('diskon'));

        if ($slot !== null) {
            // Diskon hanya dimiliki voucher brosur; sumber lain otomatis kosong.
            $query->whereIn('id_voucher', DiskonBrosur::kodeSlot($slot));
        }
    }

    /**
     | Periode penggunaan: hanya aktif pada filter status USED.
     | hari_ini (bawaan) = used_at hari ini, semua = tanpa batas,
     | custom = rentang tanggal dari..sampai (inklusif, tanggal tidak valid diabaikan).
     */
    private function terapkanPeriode(Builder $query, Request $request): void
    {
        if (strtoupper(trim((string) $request->query('status'))) !== 'USED') {
            return;
        }

        [$dari, $sampai] = $this->periodeUsed($request);

        if ($dari !== null) {
            $query->whereDate('used_at', '>=', $dari);
        }

        if ($sampai !== null) {
            $query->whereDate('used_at', '<=', $sampai);
        }
    }

    /**
     | Batas tanggal penggunaan [dari, sampai]. Item boleh null (tanpa batas).
     */
    private function periodeUsed(Request $request): array
    {
        $periode = in_array($request->query('periode'), ['semua', 'custom'], true)
            ? (string) $request->query('periode')
            : 'hari_ini';

        if ($periode === 'hari_ini') {
            return [today(), today()];
        }

        if ($periode === 'custom') {
            return [
                $this->tanggalKueri($request->query('dari')),
                $this->tanggalKueri($request->query('sampai')),
            ];
        }

        return [null, null];
    }

    /**
     | Tanggal dari query string; null bila tidak valid.
     */
    private function tanggalKueri(?string $nilai): ?Carbon
    {
        if ($nilai === null || $nilai === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        try {
            return Carbon::parse($nilai)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     | Jumlah voucher brosur berstatus USED sesuai filter aktif
     | (pencarian + diskon + periode penggunaan).
     | Tampil hanya saat memfilter status USED, tanpa sumber atau dengan sumber Brosur.
     */
    private function penggunaanBrosur(Request $request): ?int
    {
        $status = strtoupper(trim((string) $request->query('status')));

        if ($status !== 'USED') {
            return null;
        }

        $sumber = strtoupper(trim((string) $request->query('source')));

        if ($sumber !== '' && $sumber !== 'BRO') {
            return null;
        }

        $query = Voucher::query()
            ->where('kategori', 'BRO')
            ->where('status', 'USED');

        $this->terapkanPencarian($query, $request);
        $this->terapkanDiskon($query, $request);
        $this->terapkanPeriode($query, $request);

        return $query->count();
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
        return Brosur::url('brochure_voucher');
    }

    /**
     | Deskripsi periode penggunaan untuk metadata PDF.
     */
    private function labelPeriode(Request $request): ?string
    {
        $status = strtoupper(trim((string) $request->query('status')));

        if ($status !== 'USED') {
            return null;
        }

        $periode = in_array($request->query('periode'), ['semua', 'custom'], true)
            ? (string) $request->query('periode')
            : 'hari_ini';

        return match ($periode) {
            'semua' => 'Digunakan: keseluruhan',
            'custom' => (function () use ($request) {
                [$dari, $sampai] = $this->periodeUsed($request);

                if ($dari === null && $sampai === null) {
                    return 'Digunakan: keseluruhan';
                }

                return 'Digunakan '.($dari?->format('d-m-Y') ?? '?')
                    .' s/d '.($sampai?->format('d-m-Y') ?? '?');
            })(),
            default => 'Digunakan: hari ini',
        };
    }
}
