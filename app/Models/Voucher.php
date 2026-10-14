<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'voucher';

    protected $primaryKey = 'id_voucher';

    public $timestamps = false;

    protected $fillable = [
        'kode_voucher',
        'kategori',
        'status',
        'used_at',
        'id_admin',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];
}
