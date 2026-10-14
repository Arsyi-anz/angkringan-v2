<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Customer extends Model
{
    protected $table = 'customer';

    protected $primaryKey = 'id_customer';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'no_wa',
        'lahir_tgl',
        'lahir_bln',
        'setuju_pesan',
    ];

    protected $casts = [
        'setuju_pesan' => 'boolean',
        'lahir_tgl' => 'integer',
        'lahir_bln' => 'integer',
    ];

    protected $appends = [
        'name',
        'whatsapp',
        'wa_consent',
        'birth_date',
        'visits_count',
        'hampers_eligible',
    ];

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'id_customer', 'id_customer');
    }

    public function hampers(): HasMany
    {
        return $this->hasMany(Hampers::class, 'id_customer', 'id_customer');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->nama);
    }

    protected function whatsapp(): Attribute
    {
        return Attribute::get(fn () => $this->no_wa);
    }

    protected function waConsent(): Attribute
    {
        return Attribute::get(fn () => (bool) $this->setuju_pesan);
    }

    /**
     * Tanggal lahir (tahun dummy) dari lahir_tgl + lahir_bln, null bila belum diisi.
     */
    protected function birthDate(): Attribute
    {
        return Attribute::get(function () {
            if ($this->lahir_tgl === null || $this->lahir_bln === null) {
                return null;
            }

            return Carbon::createSafe(2000, (int) $this->lahir_bln, (int) $this->lahir_tgl);
        });
    }

    protected function visitsCount(): Attribute
    {
        return Attribute::get(function () {
            $count = $this->attributes['visits_count'] ?? null;

            return (int) ($count ?? $this->kunjungan()->count());
        });
    }

    /**
     * Layak menerima hampers: punya tanggal lahir dan belum klaim tahun berjalan.
     */
    protected function hampersEligible(): Attribute
    {
        return Attribute::get(function () {
            if ($this->lahir_tgl === null || $this->lahir_bln === null) {
                return false;
            }

            $claimed = $this->attributes['claimed_this_year'] ?? null;

            if ($claimed === null) {
                $claimed = $this->hampers()->where('tahun_kuota', now()->year)->count();
            }

            return (int) $claimed === 0;
        });
    }
}
