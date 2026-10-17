@extends('layouts.ang-app')

@section('title', 'Dashboard')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-dashboard.css') }}">
@endpush

@php
    /*
     | Variabel yang dikirim dari controller:
     | $totalCustomers, $vouchersCreated, $vouchersUsed, $hampersEligible  (angka)
     | $voucherSources : [['code' => 'BRO', 'label' => 'Brosur', 'count' => 502], ...]
     | $unusedVouchers, $withBirthDate, $withConsent  (angka)
     | $latestCustomers : koleksi customer (name, whatsapp, birth_date, visits_count, hampers_eligible)
     */
    $fmt = fn ($n) => number_format($n ?? 0, 0, ',', '.');
    $maxSource = max(collect($voucherSources ?? [])->max('count') ?? 0, 1);
@endphp

@section('content')
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Dashboard</strong></div>
        @include('partials.ang-user-menu')
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Dashboard</h1>
                <p class="ang-lead">Ringkasan customer, voucher, dan aktivitas loyalty Angkringan.</p>
            </div>
            <div class="ang-head-actions">
                <button type="button" class="ang-btn ang-btn-primary" data-ang-modal="ang-customer-modal">
                    Tambah customer
                </button>
                <a href="{{ Route::has('admin.vouchers.index') ? route('admin.vouchers.index') : '#' }}" class="ang-btn">Kelola voucher</a>
            </div>
        </div>

        <section class="ang-stats" aria-label="Ringkasan">
            <div class="ang-stat">
                <span class="ang-stat-label">Total customer</span>
                <span class="ang-stat-value">{{ $fmt($totalCustomers ?? 0) }}</span>
                <span class="ang-stat-note">Nomor WA terdaftar</span>
            </div>
            <div class="ang-stat">
                <span class="ang-stat-label">Voucher dibuat</span>
                <span class="ang-stat-value">{{ $fmt($vouchersCreated ?? 0) }}</span>
                <span class="ang-stat-note">Semua kanal promosi</span>
            </div>
            <div class="ang-stat">
                <span class="ang-stat-label">Voucher digunakan</span>
                <span class="ang-stat-value">{{ $fmt($vouchersUsed ?? 0) }}</span>
                <span class="ang-stat-note">Status redeemed</span>
            </div>
            <div class="ang-stat is-key">
                <span class="ang-stat-label">Layak hampers</span>
                <span class="ang-stat-value">{{ $fmt($hampersEligible ?? 0) }}</span>
                <span class="ang-stat-note">Berdasarkan aturan saat ini</span>
            </div>
        </section>

        <div class="ang-dash-grid">
            <section class="ang-panel">
                <div class="ang-panel-head">
                    <h2 class="ang-panel-title">Performa sumber voucher</h2>
                    <p class="ang-panel-sub">Jumlah voucher dibuat per kanal</p>
                </div>
                <div class="ang-panel-body">
                    <ul class="ang-sources">
                        @forelse ($voucherSources ?? [] as $s)
                            <li class="ang-source">
                                <span class="ang-chip">{{ $s['code'] }}</span>
                                <span class="ang-source-name">{{ $s['label'] }}</span>
                                <span class="ang-track"><span class="ang-fill" style="width: {{ $s['count'] > 0 ? max(($s['count'] / $maxSource) * 100, 2) : 0 }}%"></span></span>
                                <span class="ang-source-count">{{ $fmt($s['count']) }}</span>
                            </li>
                        @empty
                            <li class="ang-muted">Belum ada voucher.</li>
                        @endforelse
                    </ul>
                    <div class="ang-note">
                        <strong>Cara membaca</strong>
                        <p>Bandingkan jumlah voucher yang dibagikan dan yang di-redeem untuk tahu kanal mana yang paling menghasilkan kunjungan dine-in.</p>
                    </div>
                </div>
            </section>

            <section class="ang-panel">
                <div class="ang-panel-head ang-panel-head-row">
                    <div>
                        <h2 class="ang-panel-title">Aktivitas program</h2>
                        <p class="ang-panel-sub">Hal yang perlu diperhatikan</p>
                    </div>
                </div>
                <ul class="ang-activity">
                    <li>
                        <span>
                            <span class="ang-activity-name">Voucher belum digunakan</span>
                            <span class="ang-activity-sub">Menunggu validasi saat transaksi</span>
                        </span>
                        <span class="ang-activity-num is-alert">{{ $fmt($unusedVouchers ?? 0) }}</span>
                    </li>
                    <li>
                        <span>
                            <span class="ang-activity-name">Data ulang tahun customer</span>
                            <span class="ang-activity-sub">Data untuk ucapan ulang tahun</span>
                        </span>
                        <span class="ang-activity-num">{{ $fmt($withBirthDate ?? 0) }}</span>
                    </li>
                    <li>
                        <span>
                            <span class="ang-activity-name">Broadcast WA</span>
                            <span class="ang-activity-sub">Customer yang memberi persetujuan</span>
                        </span>
                        <span class="ang-activity-num">{{ $fmt($withConsent ?? 0) }}</span>
                    </li>
                </ul>
            </section>
        </div>

        <section class="ang-panel">
            <div class="ang-panel-head ang-panel-head-row">
                <div>
                    <h2 class="ang-panel-title">Customer terbaru</h2>
                    <p class="ang-panel-sub">Database customer dine-in</p>
                </div>
                <a href="{{ Route::has('admin.customers.index') ? route('admin.customers.index') : '#' }}" class="ang-btn ang-btn-sm">Lihat semua</a>
            </div>
            <div class="ang-table-wrap">
                <table class="ang-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Tanggal lahir</th>
                            <th>Kunjungan dine-in</th>
                            <th>Kelayakan hampers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestCustomers ?? [] as $c)
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
                                <td>
                                    @if ($c->birth_date)
                                        {{ $c->birth_date->translatedFormat('d M') }}
                                    @else
                                        <span class="ang-muted">Belum diisi</span>
                                    @endif
                                </td>
                                <td><span class="ang-visit">{{ $c->visits_count }}</span> kunjungan</td>
                                <td>
                                    @if ($c->hampers_eligible)
                                        <span class="ang-badge ang-badge-ok">Memenuhi syarat</span>
                                    @else
                                        <span class="ang-badge ang-badge-off">Belum memenuhi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="ang-empty"><strong>Belum ada customer</strong>Data akan muncul setelah customer pertama ditambahkan.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
