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
}
