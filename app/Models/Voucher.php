<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    public const KATEGORI_LABELS = [
        'IG' => 'Instagram',
        'FB' => 'Facebook',
        'TT' => 'TikTok',
        'BRO' => 'Brosur',
    ];

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

    protected function id(): Attribute
    {
        return Attribute::get(fn () => $this->id_voucher);
    }

    protected function code(): Attribute
    {
        return Attribute::get(fn () => $this->kode_voucher);
    }

    protected function sourceLabel(): Attribute
    {
        return Attribute::get(fn () => self::KATEGORI_LABELS[$this->kategori] ?? $this->kategori);
    }

    protected function isUsed(): Attribute
    {
        return Attribute::get(fn () => $this->status === 'USED');
    }
}
