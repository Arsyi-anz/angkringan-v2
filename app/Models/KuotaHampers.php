<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuotaHampers extends Model
{
    protected $table = 'kuota_hampers';

    protected $primaryKey = 'tahun';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'tahun',
        'kuota_maks',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'kuota_maks' => 'integer',
    ];

    /**
     * Kuota hampers untuk tahun berjalan (default 100 bila belum ada barisnya).
     */
    public static function tahun(int $tahun): int
    {
        $kuota = static::query()->where('tahun', $tahun)->value('kuota_maks');

        return $kuota === null ? 100 : (int) $kuota;
    }
}
