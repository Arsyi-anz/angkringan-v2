<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\CheckVoucherRequest;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VoucherController extends Controller
{
    public function validatePage(): View
    {
        return $this->render(session('result'));
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

    private function render(?array $result): View
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
}
