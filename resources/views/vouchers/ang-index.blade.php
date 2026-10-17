@extends('layouts.ang-app')

@section('title', 'Manajemen Voucher')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-validate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-voucher.css') }}">
@endpush

@php
    /*
     | Variabel dari controller:
     | $channels : [['code'=>'IG','name'=>'Instagram','count'=>1], ['code'=>'FB',...], ['code'=>'TT',...], ['code'=>'BRO','name'=>'Brosur',...]]
     | $limits   : ['brochure' => 2000, 'digital' => 50]  (digital = IG + FB + TT digabung)
     | $vouchers : paginator (code, source_code, source_label, created_at, used_at, is_used)
     | Route: admin.vouchers.generate (POST), admin.vouchers.codes (GET JSON), admin.vouchers.export (GET CSV), admin.vouchers.export.pdf (GET PDF), admin.vouchers.redeem (PATCH)
     */
    $limits      = $limits ?? ['brochure' => 2000, 'digital' => 50];
    $channels    = $channels ?? [];
    $digitalUsed = collect($channels)->whereIn('code', ['IG', 'FB', 'TT'])->sum('count');
    $remainingBy = [];
    foreach ($channels as $c) {
        $remainingBy[$c['code']] = $c['code'] === 'BRO'
            ? max(0, $limits['brochure'] - $c['count'])
            : max(0, $limits['digital'] - $digitalUsed);
    }
    $fmt = fn ($n) => number_format($n ?? 0, 0, ',', '.');
@endphp

@section('content')
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Manajemen Voucher</strong></div>
        @include('partials.ang-user-menu')
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Manajemen Voucher</h1>
                <p class="ang-lead">Generate kode unik berdasarkan kanal. Kode yang sudah dibuat disimpan di daftar dan tidak boleh dipakai dua kali.</p>
            </div>
            @include('partials.ang-export-menu', [
                'csvUrl' => Route::has('admin.vouchers.export') ? route('admin.vouchers.export', request()->query()) : '#',
                'pdfUrl' => Route::has('admin.vouchers.export.pdf') ? route('admin.vouchers.export.pdf', request()->query()) : '#',
            ])
        </div>

        @if (session('status'))
            <div class="ang-flash" role="status">{{ session('status') }}</div>
        @endif

        <p class="ang-notice"><strong>Prefix kode:</strong> IG- untuk Instagram, FB- untuk Facebook, TT- untuk TikTok, dan BRO- untuk Brosur. Batas awal campaign: {{ $fmt($limits['brochure']) }} voucher brosur dan {{ $fmt($limits['digital']) }} voucher digital (gabungan IG, FB, TT). Batas ini dapat disesuaikan.</p>

        <section class="ang-chan-grid" aria-label="Kanal voucher">
            @foreach ($channels as $c)
                @php
                    $isBro = $c['code'] === 'BRO';
                    $left  = $remainingBy[$c['code']] ?? 0;
                @endphp
                <article class="ang-chan">
                    <div class="ang-chan-top">
                        <span class="ang-chip">{{ $c['code'] }}</span>
                        <span class="ang-count-tag">{{ $fmt($c['count']) }} kode</span>
                    </div>
                    <h2 class="ang-chan-name">{{ $c['name'] }}</h2>
                    <span class="ang-chan-format">Format: <span class="ang-mono">{{ $c['code'] }}-XXXXXX</span></span>
                    <span class="ang-chan-num">{{ $fmt($c['count']) }}</span>
                    <span class="ang-chan-label">Sisa kuota: <strong>{{ $fmt($left) }}</strong> dari {{ $fmt($isBro ? $limits['brochure'] : $limits['digital']) }}</span>
                    <div class="ang-chan-actions">
                        <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-gen-modal"
                                data-gen-source="{{ $c['code'] }}" data-gen-name="{{ $c['name'] }}"
                                data-gen-format="{{ $c['code'] }}-XXXXXX" data-gen-max="{{ $left }}">
                            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            Generate voucher
                        </button>
                        @if ($isBro)
                            <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-print-modal" @disabled($c['count'] < 1)>
                                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M12 4v11M7.5 11 12 15.5 16.5 11M5 20h14"/></svg>
                                Unduh brosur (ZIP)
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>

        <section class="ang-panel">
            <div class="ang-panel-head ang-panel-head-row">
                <div>
                    <h2 class="ang-panel-title">Daftar kode voucher</h2>
                    <p class="ang-panel-sub">Validasi kode, sumber, dan status penukaran</p>
                </div>
                <span class="ang-tag">Kode unik</span>
            </div>

            <form method="GET" class="ang-toolbar">
                <div class="ang-search">
                    <svg class="ang-ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode voucher" aria-label="Cari kode voucher">
                </div>
                <select class="ang-input ang-select" name="source" aria-label="Filter sumber" data-auto-submit>
                    <option value="">Semua sumber</option>
                    @foreach ($channels as $c)
                        <option value="{{ $c['code'] }}" @selected(request('source') === $c['code'])>{{ $c['name'] }}</option>
                    @endforeach
                </select>
                <span class="ang-count">{{ $fmt(method_exists($vouchers, 'total') ? $vouchers->total() : count($vouchers ?? [])) }} kode</span>
            </form>

            <div class="ang-table-wrap">
                <table class="ang-table is-narrow">
                    <thead>
                        <tr>
                            <th>Kode voucher</th>
                            <th>Sumber</th>
                            <th>Dibuat</th>
                            <th>Status</th>
                            <th class="ang-num">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers ?? [] as $v)
                            <tr>
                                <td><span class="ang-mono">{{ $v->code }}</span></td>
                                <td>{{ $v->source_label }}</td>
                                <td class="ang-nowrap">{{ \Illuminate\Support\Carbon::parse($v->created_at)->translatedFormat('d M Y') }}</td>
                                <td>
                                    @if ($v->is_used)
                                        <span class="ang-badge ang-badge-used">Sudah digunakan</span>
                                    @else
                                        <span class="ang-badge ang-badge-ok">Belum digunakan</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="ang-actions">
                                        @unless ($v->is_used)
                                            <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-redeem-modal"
                                                    data-redeem-action="{{ Route::has('admin.vouchers.redeem') ? route('admin.vouchers.redeem', $v->id) : '#' }}"
                                                    data-redeem-code="{{ $v->code }}">Tandai USED</button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="ang-empty"><strong>Tidak ada kode</strong>Ubah pencarian atau generate voucher baru.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($vouchers, 'hasPages') && $vouchers->hasPages())
                @php $pg = $vouchers->withQueryString(); @endphp
                <nav class="ang-pager" aria-label="Halaman">
                    <span>Halaman {{ $pg->currentPage() }} dari {{ $pg->lastPage() }}</span>
                    <span class="ang-pager-nav">
                        <a class="ang-btn ang-btn-sm {{ $pg->onFirstPage() ? 'ang-link-off' : '' }}" href="{{ $pg->previousPageUrl() ?? '#' }}" @if ($pg->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>
                        <a class="ang-btn ang-btn-sm {{ $pg->hasMorePages() ? '' : 'ang-link-off' }}" href="{{ $pg->nextPageUrl() ?? '#' }}" @unless ($pg->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>Berikutnya</a>
                    </span>
                </nav>
            @endif
        </section>
    </main>

    @include('partials.ang-voucher-generate-modal')
    @include('partials.ang-voucher-print-modal')
    @include('partials.ang-redeem-modal')
@endsection

@push('ang-scripts')
    <script src="{{ asset('assets/js/vendor/fflate.min.js') }}"></script>
    <script src="{{ asset('assets/js/ang-validate.js') }}"></script>
    <script src="{{ asset('assets/js/ang-vouchers.js') }}"></script>
@endpush
