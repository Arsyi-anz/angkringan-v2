<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesanUlangTahun extends Model
{
    public const JENIS_BIASA = 'BIASA';

    public const JENIS_HAMPERS = 'HAMPERS';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_TERKIRIM = 'TERKIRIM';

    public const STATUS_GAGAL = 'GAGAL';

    protected $table = 'pesan_ulang_tahun';

    protected $primaryKey = 'id_pesan';

    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'tahun',
        'jenis',
        'status',
        'wa_message_id',
        'error',
        'dikirim_at',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'dikirim_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
