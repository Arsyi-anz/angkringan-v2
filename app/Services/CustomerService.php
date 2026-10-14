<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerService
{
    /**
     * Query customer terfilter dengan total kunjungan (dihitung otomatis).
     */
    public function query(array $filters): Builder
    {
        $keyword = trim((string) ($filters['q'] ?? ''));
        $bulan = $filters['bulan'] ?? null;

        return Customer::query()
            ->withCount('kunjungan as visits_count')
            ->withCount(['hampers as claimed_this_year' => function (Builder $query) {
                $query->where('tahun_kuota', now()->year);
            }])
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $inner) use ($keyword) {
                    $inner->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('no_wa', 'like', "%{$keyword}%");
                });
            })
            ->when(! empty($bulan), function (Builder $query) use ($bulan) {
                $query->where('lahir_bln', (int) $bulan);
            })
            ->orderBy('nama');
    }

    public function paginate(array $filters, int $perPage = 15)
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function all(array $filters)
    {
        return $this->query($filters)->get();
    }
}
