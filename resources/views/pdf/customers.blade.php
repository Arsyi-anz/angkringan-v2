<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Customer</title>
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
    <h1>Data Customer Angkringan</h1>
    <div class="meta">
        Dicetak: {{ now()->format('d-m-Y H:i') }}
        @if (! empty($filters['q'])) &middot; Pencarian: "{{ $filters['q'] }}" @endif
        @if (! empty($filters['bulan'])) &middot; Bulan lahir: {{ $filters['bulan'] }} @endif
        &middot; Total: {{ $customers->count() }} customer
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>No WA</th>
                <th>Tgl Lahir</th>
                <th>Bulan Lahir</th>
                <th>Setuju Pesan</th>
                <th>Total Kunjungan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $i => $customer)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $customer->nama }}</td>
                    <td>{{ $customer->no_wa }}</td>
                    <td>{{ $customer->lahir_tgl ?? '-' }}</td>
                    <td>{{ $customer->lahir_bln ?? '-' }}</td>
                    <td>{{ $customer->setuju_pesan ? 'Ya' : 'Tidak' }}</td>
                    <td>{{ $customer->visits_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
