<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Voucher</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { margin-bottom: 12px; color: #555; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 6px; text-align: left; }
        th { background: #eee; }
        tr:nth-child(even) td { background: #f7f7f7; }
    </style>
</head>
<body>
    @if (! empty($meta))
        <h1>Data Voucher Angkringan</h1>
        <div class="meta">
            Dicetak: {{ now()->format('d-m-Y H:i') }}
            @if (! empty($filters['q'])) &middot; Pencarian: "{{ $filters['q'] }}" @endif
            @if (! empty($filters['source'])) &middot; Kanal: {{ $filters['source'] }} @endif
            @if (! empty($filters['status'])) &middot; Status: {{ $filters['status'] === 'AVAILABLE' ? 'Belum digunakan' : 'USED' }} @endif
            @if (! empty($filters['diskon'])) &middot; {{ $filters['diskon'] }} @endif
            @if (! empty($filters['periode'])) &middot; {{ $filters['periode'] }} @endif
            &middot; Total: {{ $meta }} voucher
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Sumber</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th>Digunakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vouchers as $i => $voucher)
                <tr>
                    <td>{{ $offset + $i + 1 }}</td>
                    <td>{{ $voucher->kode_voucher }}</td>
                    <td>{{ $voucher->source_label }}</td>
                    <td>{{ $voucher->is_used ? 'USED' : 'AVAILABLE' }}</td>
                    <td>{{ $voucher->created_at }}</td>
                    <td>{{ $voucher->used_at ?? '-' }}</td>
                </tr>
            @empty
                @unless (! empty($meta))
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada data.</td>
                    </tr>
                @endunless
            @endforelse
        </tbody>
    </table>
</body>
</html>
