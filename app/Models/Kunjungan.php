<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kunjungan extends Model
{
    protected $table = 'kunjungan';

    protected $primaryKey = 'id_kunjungan';

    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'tgl_kunjungan',
        'id_admin',
    ];

    protected $casts = [
        'tgl_kunjungan' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }
}
