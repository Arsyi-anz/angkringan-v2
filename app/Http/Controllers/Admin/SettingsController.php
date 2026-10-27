<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreBrochureRequest;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\KuotaHampers;
use App\Support\Brosur;
use App\Support\Pengaturan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $minVisits = Pengaturan::ambilInt('min_visits', Pengaturan::DEFAULT_MIN_VISITS);
        $windowDays = Pengaturan::ambilInt('window_days', Pengaturan::DEFAULT_WINDOW_DAYS);
        $quotaYear = KuotaHampers::tahun(now()->year);
        $brochures = Brosur::semua();

        return view('settings.ang-index', compact('minVisits', 'windowDays', 'quotaYear', 'brochures'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        Pengaturan::simpan('min_visits', (string) $request->integer('min_visits'));
        Pengaturan::simpan('window_days', (string) $request->integer('window_days'));

        KuotaHampers::updateOrCreate(
            ['tahun' => now()->year],
            ['kuota_maks' => $request->integer('annual_quota')]
        );

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Parameter program berhasil disimpan.');
    }

    public function brochure(StoreBrochureRequest $request): RedirectResponse
    {
        File::ensureDirectoryExists(Brosur::directory());

        foreach (array_column(Brosur::semua(), 'key') as $key) {
            $file = $request->file($key);

            if (! $file) {
                continue;
            }

            $extension = $file->getClientOriginalExtension()
                ?: ($file->extension() ?: 'jpg');

            $filename = sprintf(
                '%s-%s-%s.%s',
                $key,
                now()->format('YmdHis'),
                Str::lower(Str::random(6)),
                Str::lower($extension)
            );

            $file->move(Brosur::directory(), $filename);

            if ($lama = Pengaturan::ambil($key)) {
                File::delete(Brosur::directory().DIRECTORY_SEPARATOR.$lama);
            }

            Pengaturan::simpan($key, $filename);
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Brosur program berhasil diperbarui.');
    }
}
