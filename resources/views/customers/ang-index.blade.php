@extends('layouts.ang-app')

@section('title', 'Data Customer')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
@endpush

@section('content')
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Data Customer</strong></div>
        @include('partials.ang-user-menu')
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Data Customer</h1>
                <p class="ang-lead">Satu nomor WhatsApp untuk satu member. Tanggal lahir bersifat opsional dan persetujuan pesan wajib dicatat.</p>
            </div>
            <button type="button" class="ang-btn ang-btn-primary" data-ang-modal="ang-customer-modal">
                Tambah customer
            </button>
        </div>

        @if (session('status'))
            <div class="ang-flash" role="status">{{ session('status') }}</div>
        @endif

        {{-- Catatan: kalau $customers memakai paginate(), hitung statistik di controller. --}}
        <section class="ang-stats" aria-label="Ringkasan">
            <div class="ang-stat">
                <span class="ang-stat-label">Total member</span>
                <span class="ang-stat-value">{{ $customers->count() }}</span>
                <span class="ang-stat-note">Semua data tersimpan</span>
            </div>
            <div class="ang-stat">
                <span class="ang-stat-label">Tanggal lahir terisi</span>
                <span class="ang-stat-value">{{ $customers->whereNotNull('birth_date')->count() }}</span>
                <span class="ang-stat-note">Untuk ucapan ulang tahun</span>
            </div>
            <div class="ang-stat">
                <span class="ang-stat-label">Persetujuan WA</span>
                <span class="ang-stat-value">{{ $customers->where('wa_consent', true)->count() }}</span>
                <span class="ang-stat-note">Setuju menerima pesan</span>
            </div>
            <div class="ang-stat is-key">
                <span class="ang-stat-label">Total kunjungan</span>
                <span class="ang-stat-value">{{ $customers->sum('visits_count') }}</span>
                <span class="ang-stat-note">Kunjungan dine-in tercatat</span>
            </div>
        </section>

        <section class="ang-panel">
            <form method="GET" class="ang-toolbar">
                <div class="ang-search">
                    <svg class="ang-ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor WA" aria-label="Cari customer">
                </div>
                <span class="ang-count">{{ $customers->count() }} customer</span>
                <a href="{{ Route::has('admin.customers.export') ? route('admin.customers.export') : '#' }}" class="ang-btn">
                    <svg class="ang-ico" viewBox="0 0 24 24"><path d="M12 4v11M7.5 11 12 15.5 16.5 11M5 20h14"/></svg>
                    Export CSV
                </a>
            </form>

            <div class="ang-table-wrap">
                <table class="ang-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Tanggal lahir</th>
                            <th>Kunjungan</th>
                            <th>Persetujuan WA</th>
                            <th class="ang-num">Aksi</th>
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
                                <td>
                                    @if ($c->birth_date)
                                        {{ $c->birth_date->translatedFormat('d F') }}
                                    @else
                                        <span class="ang-muted">Belum diisi</span>
                                    @endif
                                </td>
                                <td><span class="ang-visit">{{ $c->visits_count }}</span></td>
                                <td>
                                    @if ($c->wa_consent)
                                        <span class="ang-badge ang-badge-ok">Disetujui</span>
                                    @else
                                        <span class="ang-badge ang-badge-off">Belum setuju</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="ang-actions">
                                        <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-visit-modal" data-customer-id="{{ $c->id_customer }}">+ Kunjungan</button>
                                        <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-customer-modal"
                                                data-edit-id="{{ $c->id_customer }}"
                                                data-edit-nama="{{ $c->nama }}"
                                                data-edit-wa="{{ $c->no_wa }}"
                                                data-edit-birth="{{ optional($c->birth_date)->format('Y-m-d') }}"
                                                data-edit-consent="{{ $c->wa_consent ? '1' : '0' }}">Edit</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="ang-empty">
                                        <strong>Belum ada customer</strong>
                                        Klik "Tambah customer" untuk mencatat member pertama.
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
