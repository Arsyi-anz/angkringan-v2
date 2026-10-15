@extends('layouts.ang-app')

@section('title', 'Pengaturan Program')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-settings.css') }}">
@endpush

@php
    /*
     | Variabel dari controller:
     | $minVisits, $windowDays, $quotaYear  : nilai pengaturan saat ini
     | $brochures : [['key'=>'brochure_main','title'=>..,'note'=>..,'url'=>..], ...]
     |   kalau tidak dikirim, dipakai dua gambar bawaan di public/images
     */
    $brochures = $brochures ?? [
        ['key' => 'brochure_main',    'title' => 'Poster promo',   'note' => 'Gambar yang dibagikan ke customer', 'url' => asset('images/brosur-poster.jpeg')],
        ['key' => 'brochure_voucher', 'title' => 'Lembar voucher', 'note' => 'Bentuk voucher cetak yang dibawa customer', 'url' => asset('images/brosur-voucher.jpeg')],
    ];
@endphp

@section('content')
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Pengaturan Program</strong></div>
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Pengaturan Program</h1>
                <p class="ang-lead">Parameter program loyalty yang bisa dikelola admin. Perubahan di halaman ini memengaruhi evaluasi kelayakan hampers.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="ang-flash" role="status">{{ session('status') }}</div>
        @endif

        <div class="ang-settings-grid">
            <section class="ang-panel">
                <div class="ang-panel-head">
                    <h2 class="ang-panel-title">Syarat birthday hampers</h2>
                    <p class="ang-panel-sub">Nilai bawaan mengikuti dokumen program</p>
                </div>
                <form method="POST" action="{{ Route::has('admin.settings.update') ? route('admin.settings.update') : '#' }}" class="ang-form-pad" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="ang-form-grid">
                        <div class="ang-field">
                            <label class="ang-label" for="ang-s-min">Minimal kunjungan dine-in</label>
                            <div class="ang-suffix">
                                <input class="ang-input @error('min_visits') is-invalid @enderror" id="ang-s-min" name="min_visits" type="number" min="1" max="100" step="1" inputmode="numeric" value="{{ old('min_visits', $minVisits ?? 5) }}" required>
                                <span class="ang-suffix-unit">kunjungan</span>
                            </div>
                            @error('min_visits')<span class="ang-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ang-field">
                            <label class="ang-label" for="ang-s-window">Rentang hari sebelum ulang tahun</label>
                            <div class="ang-suffix">
                                <input class="ang-input @error('window_days') is-invalid @enderror" id="ang-s-window" name="window_days" type="number" min="1" max="365" step="1" inputmode="numeric" value="{{ old('window_days', $windowDays ?? 90) }}" required>
                                <span class="ang-suffix-unit">hari</span>
                            </div>
                            @error('window_days')<span class="ang-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ang-field">
                            <label class="ang-label" for="ang-s-quota">Kuota hampers per tahun</label>
                            <div class="ang-suffix">
                                <input class="ang-input @error('annual_quota') is-invalid @enderror" id="ang-s-quota" name="annual_quota" type="number" min="0" step="1" inputmode="numeric" value="{{ old('annual_quota', $quotaYear ?? 100) }}" required>
                                <span class="ang-suffix-unit">hampers</span>
                            </div>
                            @error('annual_quota')<span class="ang-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="ang-form-actions">
                        <button type="submit" class="ang-btn ang-btn-primary">Simpan parameter</button>
                    </div>
                </form>
            </section>

            <section class="ang-panel">
                <div class="ang-panel-head">
                    <h2 class="ang-panel-title">Aturan data member</h2>
                    <p class="ang-panel-sub">Ketentuan yang perlu diperhatikan</p>
                </div>
                <ul class="ang-rules">
                    <li>
                        <span class="ang-rule-ico"><svg class="ang-ico" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-3.7 3.4-5.5 7-5.5s6.3 1.8 7 5.5"/></svg></span>
                        <span><span class="ang-rule-name">Satu nomor WA = satu member</span><span class="ang-rule-sub">Nomor WhatsApp menjadi kunci identitas</span></span>
                    </li>
                    <li>
                        <span class="ang-rule-ico"><svg class="ang-ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16 9.5"/></svg></span>
                        <span><span class="ang-rule-name">Persetujuan WA wajib</span><span class="ang-rule-sub">Sediakan opsi berhenti menerima pesan</span></span>
                    </li>
                    <li>
                        <span class="ang-rule-ico"><svg class="ang-ico" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/></svg></span>
                        <span><span class="ang-rule-name">Tanggal lahir opsional</span><span class="ang-rule-sub">Hanya tanggal dan bulan diperlukan</span></span>
                    </li>
                    <li>
                        <span class="ang-rule-ico"><svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 10v10h16V10"/><path d="M3 10l1.5-5h15L21 10c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3z"/></svg></span>
                        <span><span class="ang-rule-name">Take-away tidak dihitung</span><span class="ang-rule-sub">Khusus kunjungan dine-in</span></span>
                    </li>
                </ul>
            </section>
        </div>

        <section class="ang-panel">
            <div class="ang-panel-head">
                <h2 class="ang-panel-title">Brosur program</h2>
                <p class="ang-panel-sub">Pembanding bentuk asli brosur dan voucher saat validasi, agar voucher palsu mudah dikenali. Klik gambar untuk memperbesar.</p>
            </div>

            <form id="ang-broc-form" method="POST" action="{{ Route::has('admin.settings.brochure') ? route('admin.settings.brochure') : '#' }}" enctype="multipart/form-data" class="ang-form-pad">
                @csrf
                <div class="ang-broc-grid">
                    @foreach ($brochures as $b)
                        <div class="ang-broc">
                            <button type="button" class="ang-broc-view" data-ang-modal="ang-zoom-modal"
                                    data-ang-zoom-src="{{ $b['url'] }}" data-ang-zoom-title="{{ $b['title'] }}"
                                    aria-label="Perbesar {{ $b['title'] }}">
                                <img src="{{ $b['url'] }}" alt="{{ $b['title'] }}" loading="lazy">
                            </button>
                            <div class="ang-broc-meta">
                                <span>
                                    <span class="ang-broc-title">{{ $b['title'] }}</span>
                                    <span class="ang-broc-note">{{ $b['note'] }}</span>
                                </span>
                                <span class="ang-pending">Belum disimpan</span>
                            </div>
                            <div>
                                <input class="ang-sr ang-file" type="file" id="ang-file-{{ $b['key'] }}" name="{{ $b['key'] }}" accept="image/jpeg,image/png,image/webp">
                                <label class="ang-btn ang-btn-sm" for="ang-file-{{ $b['key'] }}">Ganti gambar</label>
                                <span class="ang-error" data-broc-error hidden></span>
                                @error($b['key'])<span class="ang-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="ang-broc-foot">
                    <p>Format JPG, PNG, atau WebP, maksimal 4 MB. Keabsahan voucher tetap ditentukan dari kode di menu Validasi Voucher, bukan dari gambar.</p>
                    <button type="button" class="ang-btn" data-broc-cancel hidden>Batalkan</button>
                    <button type="submit" class="ang-btn ang-btn-primary" data-broc-save disabled>Simpan brosur</button>
                </div>
            </form>
        </section>
    </main>

    <dialog id="ang-zoom-modal" class="ang-modal ang-lightbox" aria-labelledby="ang-zoom-title">
        <div class="ang-modal-head">
            <h2 class="ang-modal-title" id="ang-zoom-title" data-zoom-title>Brosur</h2>
            <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="ang-lightbox-body"><img src="" alt="Pratinjau brosur"></div>
    </dialog>
@endsection

@push('ang-scripts')
    <script src="{{ asset('assets/js/ang-settings.js') }}"></script>
@endpush
