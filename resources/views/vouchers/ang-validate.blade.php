@extends('layouts.ang-app')

@section('title', 'Validasi Voucher')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-validate.css') }}">
@endpush

@section('content')
    {{--
        Variabel dari controller:
        $result   : null, atau ['state' => 'valid'|'used'|'invalid', 'code' => 'BRO-XXXXXX', 'voucher' => model|null]
                    voucher: id, code, source_label, used_at
        $vouchers : voucher terbaru (code, source_label, used_at, is_used)
        Route: admin.vouchers.check (POST), admin.vouchers.redeem (PATCH, parameter voucher)
    --}}
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Validasi Voucher</strong></div>
        @include('partials.ang-user-menu')
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Validasi Voucher</h1>
                <p class="ang-lead">Masukkan kode yang ditunjukkan customer sebelum transaksi dine-in. Voucher yang sudah USED tidak dapat dipakai kembali.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="ang-flash" role="status">{{ session('status') }}</div>
        @endif

        <div class="ang-val-grid">
            <section class="ang-panel">
                <div class="ang-panel-head ang-panel-head-row">
                    <div>
                        <h2 class="ang-panel-title">Cek kode voucher</h2>
                        <p class="ang-panel-sub">Periksa status kode di database</p>
                    </div>
                    <span class="ang-tag">Hanya dine-in</span>
                </div>

                <form method="POST" action="{{ Route::has('admin.vouchers.check') ? route('admin.vouchers.check') : '#' }}" class="ang-form-pad" novalidate>
                    @csrf
                    <div class="ang-field">
                        <label class="ang-label" for="ang-code">Kode voucher</label>
                        <input class="ang-input ang-code-input @error('code') is-invalid @enderror" id="ang-code" name="code" type="text"
                               value="{{ old('code', $result['code'] ?? '') }}" placeholder="Contoh: BRO-7KQ2M9"
                               maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" required autofocus>
                        @error('code')<span class="ang-error">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="ang-btn ang-btn-primary ang-btn-block">
                        Validasi kode
                        <svg class="ang-ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>

                    @if (!empty($result))
                        @php $v = $result['voucher'] ?? null; @endphp
                        @if ($result['state'] === 'valid' && $v)
                            <div class="ang-result is-valid" role="status">
                                <span>
                                    <strong>Voucher valid</strong>
                                    <span class="ang-result-sub"><span class="ang-mono">{{ $v->code }}</span> &middot; {{ $v->source_label }} &middot; belum digunakan</span>
                                </span>
                                <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-redeem-modal"
                                        data-redeem-action="{{ Route::has('admin.vouchers.redeem') ? route('admin.vouchers.redeem', $v->id) : '#' }}"
                                        data-redeem-code="{{ $v->code }}">Tandai USED</button>
                            </div>
                        @elseif ($result['state'] === 'used' && $v)
                            <div class="ang-result is-used" role="alert">
                                <span>
                                    <strong>Voucher sudah digunakan</strong>
                                    <span class="ang-result-sub"><span class="ang-mono">{{ $v->code }}</span>
                                        @if ($v->used_at) &middot; {{ \Illuminate\Support\Carbon::parse($v->used_at)->translatedFormat('d M Y, H:i') }} @endif
                                        &middot; tidak dapat dipakai kembali</span>
                                </span>
                            </div>
                        @else
                            <div class="ang-result is-invalid" role="alert">
                                <span>
                                    <strong>Kode tidak ditemukan</strong>
                                    <span class="ang-result-sub">Periksa kembali kode yang dimasukkan.</span>
                                </span>
                            </div>
                        @endif
                    @endif

                    <p class="ang-form-aside">Curiga voucher palsu? <a class="ang-link" href="{{ Route::has('admin.settings.edit') ? route('admin.settings.edit') : '#' }}">Bandingkan dengan brosur asli</a>.</p>
                </form>
            </section>

            <section class="ang-panel">
                <div class="ang-panel-head">
                    <h2 class="ang-panel-title">Ketentuan voucher</h2>
                    <p class="ang-panel-sub">Sesuai rencana pemasaran</p>
                </div>
                <ol class="ang-steps">
                    <li><span class="ang-step-no">1</span><span><span class="ang-step-name">Hanya dine-in</span><span class="ang-step-sub">Tidak berlaku untuk take-away</span></span></li>
                    <li><span class="ang-step-no">2</span><span><span class="ang-step-name">Satu voucher per transaksi</span><span class="ang-step-sub">Berlaku sesuai periode dan menu</span></span></li>
                    <li><span class="ang-step-no">3</span><span><span class="ang-step-name">Redeem sekali</span><span class="ang-step-sub">Ubah status menjadi USED setelah transaksi</span></span></li>
                </ol>
            </section>
        </div>

        <!-- <section class="ang-panel">
            <div class="ang-panel-head">
                <h2 class="ang-panel-title">Voucher terbaru</h2>
                <p class="ang-panel-sub">Tandai USED setelah transaksi dine-in selesai</p>
            </div>
            <div class="ang-table-wrap">
                <table class="ang-table is-narrow">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Sumber</th>
                            <th>Status</th>
                            <th class="ang-num">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers ?? [] as $v)
                            <tr>
                                <td><span class="ang-mono">{{ $v->code }}</span></td>
                                <td>{{ $v->source_label }}</td>
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
                            <tr><td colspan="4"><div class="ang-empty"><strong>Belum ada voucher</strong>Voucher akan muncul setelah dibuat di Manajemen Voucher.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section> -->
    </main>

    @include('partials.ang-redeem-modal')
@endsection

@push('ang-scripts')
    <script src="{{ asset('assets/js/ang-validate.js') }}"></script>
@endpush
