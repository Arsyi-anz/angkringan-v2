<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hampers extends Model
{
    protected $table = 'hampers';

    protected $primaryKey = 'id_hampers';

    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'id_pesan',
        'tahun_kuota',
        'kode_klaim',
        'batas_ambil',
        'status',
        'diambil_at',
        'id_admin',
    ];

    protected $casts = [
        'batas_ambil' => 'date',
        'diambil_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
