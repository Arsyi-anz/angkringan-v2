<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_customer' => $this->id_customer,
            'nama' => $this->nama,
            'no_wa' => $this->no_wa,
            'lahir_tgl' => $this->lahir_tgl,
            'lahir_bln' => $this->lahir_bln,
            'setuju_pesan' => (bool) $this->setuju_pesan,
            'total_kunjungan' => $this->visits_count,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
