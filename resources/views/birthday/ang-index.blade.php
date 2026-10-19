@extends('layouts.ang-app')

@section('title', 'Birthday & Hampers')

@push('ang-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ang-customer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-validate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-birthday.css') }}">
@endpush

@php
    /*
     | Variabel dari controller:
     | $quotaYear, $eligibleCount, $withBirthDate (angka)
     | $minVisits (mis. 5), $windowDays (mis. 90)  -> sebaiknya dari Pengaturan Program
     | $members : name, whatsapp, birth_date, visits_window_count, hampers_eligible (bool),
     |            status_pesan (TERKIRIM | GAGAL | null untuk tahun berjalan),
     |            hampers_tahun (model Hampers | null untuk tahun berjalan),
     |            hampers_batas (Carbon batas klaim | null bila tanggal lahir tidak valid)
     */
    $minVisits  = $minVisits ?? 5;
    $windowDays = $windowDays ?? 90;
@endphp

@section('content')
    <header class="ang-topbar">
        <label for="ang-nav-toggle" class="ang-burger" aria-label="Buka menu">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </label>
        <div class="ang-crumb">Workspace / <strong>Birthday &amp; Hampers</strong></div>
        @include('partials.ang-user-menu')
    </header>

    <main class="ang-page">
        <div class="ang-page-head">
            <div>
                <h1 class="ang-title">Birthday &amp; Hampers</h1>
                <p class="ang-lead">Sistem mengevaluasi minimal {{ $minVisits }} kunjungan dine-in dalam {{ $windowDays }} hari sebelum ulang tahun, persetujuan WA, dan kuota tahunan. Klaim hampers tetap dilakukan langsung di outlet.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="ang-flash" role="status">{{ session('status') }}</div>
        @endif

        <section class="ang-stats is-3" aria-label="Ringkasan">
            <div class="ang-stat">
                <span class="ang-stat-label">Kuota tahunan</span>
                <span class="ang-stat-value">{{ $quotaYear ?? 100 }}</span>
                <span class="ang-stat-note">Tahun kalender</span>
            </div>
            <div class="ang-stat is-key">
                <span class="ang-stat-label">Memenuhi syarat</span>
                <span class="ang-stat-value">{{ $eligibleCount ?? 0 }}</span>
                <span class="ang-stat-note">Sesuai data saat ini</span>
            </div>
            <div class="ang-stat">
                <span class="ang-stat-label">Tanggal lahir tersimpan</span>
                <span class="ang-stat-value">{{ $withBirthDate ?? 0 }}</span>
                <span class="ang-stat-note">Tanggal dan bulan</span>
            </div>
        </section>

        <p class="ang-notice"><strong>Alur pesan:</strong> pada hari ulang tahun, member dengan consent menerima pesan. Jika belum memenuhi syarat, kirim ucapan saja. Jika memenuhi syarat dan kuota tersedia, sertakan instruksi menunjukkan chat WhatsApp kepada kasir untuk mengambil hampers.</p>

        <section class="ang-panel">
            <div class="ang-panel-head ang-panel-head-row">
                <div>
                    <h2 class="ang-panel-title">Daftar evaluasi member</h2>
                    <p class="ang-panel-sub">Pratinjau kelayakan berdasarkan kunjungan yang tercatat</p>
                </div>
                <button type="button" class="ang-btn ang-btn-sm" data-ang-modal="ang-message-modal">Pratinjau pesan</button>
            </div>

            <div class="ang-table-wrap">
                <table class="ang-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Tanggal lahir</th>
                            <th>Kunjungan ({{ $windowDays }} hari)</th>
                            <th>Status pesan</th>
                            <th>Status hampers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members ?? [] as $m)
                            <tr>
                                <td>
                                    <div class="ang-person">
                                        <span class="ang-avatar">{{ strtoupper(collect(explode(' ', $m->name))->take(2)->map(fn ($w) => substr($w, 0, 1))->implode('')) }}</span>
                                        <span>
                                            <span class="ang-person-name">{{ $m->name }}</span>
                                            <span class="ang-person-wa">{{ $m->whatsapp }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if ($m->birth_date)
                                        {{ $m->birth_date->translatedFormat('d M') }}
                                    @else
                                        <span class="ang-muted">Belum diisi</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($m->visits_window_count >= $minVisits)
                                        <span class="ang-badge ang-badge-ok">Memenuhi syarat</span>
                                    @else
                                        <span class="ang-visit">{{ $m->visits_window_count }}/{{ $minVisits }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($m->status_pesan === \App\Models\PesanUlangTahun::STATUS_TERKIRIM)
                                        <span class="ang-badge ang-badge-ok">Pesan Terkirim</span>
                                    @elseif ($m->status_pesan === \App\Models\PesanUlangTahun::STATUS_GAGAL)
                                        <span class="ang-badge ang-badge-no">Pesan Gagal Dikirim</span>
                                    @else
                                        <span class="ang-badge ang-badge-off">Belum Layak</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $hampersTahun = $m->getAttribute('hampers_tahun');
                                        $hampersBatas = $m->getAttribute('hampers_batas');
                                        $lewatBatas = $hampersBatas !== null && $hampersBatas->lt(today());
                                    @endphp
                                    @if ($hampersTahun && $hampersTahun->status === 'DIAMBIL')
                                        <span class="ang-badge ang-badge-ok">Sudah KLAIM</span>
                                    @elseif ($lewatBatas)
                                        <span class="ang-badge ang-badge-no">Kadaluwarsa</span>
                                    @elseif ($m->visits_window_count >= $minVisits)
                                        <button type="button" class="ang-btn ang-btn-sm"
                                                data-ang-modal="ang-claim-modal"
                                                data-claim-action="{{ Route::has('admin.hampers.claim') ? route('admin.hampers.claim', $m) : '#' }}"
                                                data-claim-name="{{ $m->name }}">KLAIM</button>
                                    @else
                                        <span class="ang-badge ang-badge-off">Ucapan saja</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="ang-empty">
                                        <strong>Belum ada member dengan tanggal lahir</strong>
                                        Isi tanggal lahir di Data Customer agar masuk daftar evaluasi.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="ang-fineprint"><strong>Penting:</strong> penentuan kuota final mengikuti aturan prioritas: belum pernah menerima hampers, kunjungan dine-in terbanyak, lalu paling dulu memenuhi syarat.</p>
    </main>

    @include('partials.ang-message-modal')
    @include('partials.ang-claim-modal')
@endsection

@push('ang-scripts')
    <script>
        /* Isi action + nama member saat tombol KLAIM diklik (pola sama dengan ang-validate.js). */
        (function () {
            var dlg = document.getElementById('ang-claim-modal');
            if (!dlg) return;
            var form = dlg.querySelector('form');
            var nameEl = dlg.querySelector('[data-claim-code]');
            var submit = dlg.querySelector('[type="submit"]');

            document.addEventListener('click', function (e) {
                var t = e.target.closest('[data-claim-action]');
                if (!t) return;
                form.setAttribute('action', t.getAttribute('data-claim-action'));
                nameEl.textContent = t.getAttribute('data-claim-name');
                submit.disabled = false;
            });

            form.addEventListener('submit', function () { submit.disabled = true; });
        })();
    </script>
@endpush
