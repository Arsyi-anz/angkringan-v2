@extends('layouts.ang-app')

@section('title', 'Kunjungan Dine-in')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-visit.css') }}">
@endpush

@section('content')
    {{--
        Variabel dari controller:
        $customers       : customer + kunjungan (name, whatsapp, last_visit_at, visits_count, visits_90d_count)
        $customerOptions : semua customer (id, name, whatsapp) untuk dropdown di modal
    --}}
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Kunjungan Dine-in</strong></div>
        @include('partials.ang-user-menu')
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Kunjungan Dine-in</h1>
                <p class="ang-lead">Setiap kunjungan dicatat atas nama member. Maksimal satu kunjungan dihitung per customer per hari; transaksi take-away tidak dihitung.</p>
            </div>
            <button type="button" class="ang-btn ang-btn-primary" data-ang-modal="ang-visit-modal">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Catat kunjungan
            </button>
        </div>

        @if (session('status'))
            <div class="ang-flash" role="status">{{ session('status') }}</div>
        @endif

        <!-- <p class="ang-notice"><strong>Catatan operasional:</strong> dokumen menyebut ID member atau nomor WA sebagai bukti kunjungan. Data kunjungan ini menjadi dasar evaluasi syarat hampers.</p> -->

        <section class="ang-panel">
            <form method="GET" class="ang-toolbar">
                <div class="ang-search">
                    <svg class="ang-ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari customer" aria-label="Cari customer">
                </div>
                <span class="ang-count">{{ $customers->count() }} customer</span>
            </form>

            <div class="ang-table-wrap">
                <table class="ang-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Kunjungan terakhir</th>
                            <th>Jumlah kunjungan</th>
                            <th>Dalam 90 hari</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $c)
                            <tr>
                                <td>
                                    <div class="ang-person">
                                        <span class="ang-avatar">{{ strtoupper(collect(explode(' ', $c->name))->take(2)->map(fn ($w) => substr($w, 0, 1))->implode('')) }}</span>
                                        <span>
                                            <span class="ang-person-name">{{ $c->name }}</span>
                                            <span class="ang-person-wa">{{ $c->whatsapp }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="ang-date">
                                    @if ($c->last_visit_at)
                                        {{ \Illuminate\Support\Carbon::parse($c->last_visit_at)->translatedFormat('d M Y') }}
                                    @else
                                        <span class="ang-muted">Belum ada</span>
                                    @endif
                                </td>
                                <td><span class="ang-visit">{{ $c->visits_count }}</span></td>
                                <td><span class="ang-period">{{ $c->visits_90d_count }}</span><span class="ang-period-sub">kunjungan</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="ang-empty">
                                        <strong>Belum ada kunjungan</strong>
                                        Klik "Catat kunjungan" untuk mencatat kunjungan dine-in pertama.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    @include('partials.ang-visit-modal')
@endsection
