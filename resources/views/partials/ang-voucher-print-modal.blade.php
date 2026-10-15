{{-- Modal "Unduh brosur (ZIP)": menempel kode BRO ke gambar lembar voucher di browser, lalu mengunduh satu ZIP.
     Server hanya menyediakan JSON daftar kode di route admin.vouchers.codes. --}}
@php
    $tplUrl   = $voucherTemplate ?? asset('assets/img/brosur-voucher.jpg');
    $tplSize  = $voucherTemplateSize ?? ['w' => 1131, 'h' => 1600];
    // Koordinat kotak "Kode Voucher" (px, relatif ke ukuran asli gambar). Ubah jika gambar brosur diganti.
    $slots    = $voucherSlots ?? [
        ['x' => 457, 'y' => 254,  'w' => 231, 'h' => 56],
        ['x' => 457, 'y' => 782,  'w' => 231, 'h' => 57],
        ['x' => 457, 'y' => 1311, 'w' => 231, 'h' => 57],
    ];
@endphp
<dialog id="ang-print-modal" class="ang-modal ang-print" aria-labelledby="ang-print-title"
        data-codes-url="{{ Route::has('admin.vouchers.codes') ? route('admin.vouchers.codes') : '' }}"
        data-source="BRO"
        data-template="{{ $tplUrl }}"
        data-width="{{ $tplSize['w'] }}" data-height="{{ $tplSize['h'] }}"
        data-slots="{{ json_encode($slots) }}">
    <div class="ang-modal-head">
        <div>
            <h2 class="ang-modal-title" id="ang-print-title">Unduh brosur voucher (ZIP)</h2>
            <p class="ang-modal-sub">Kode ditempel ke gambar di browser ini. Server hanya mengirim daftar kode.</p>
        </div>
        <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <div class="ang-modal-body">
        <div class="ang-print-grid">
            <div class="ang-print-form">
                <div class="ang-field">
                    <label class="ang-label" for="ang-p-scope">Kode yang dipakai</label>
                    <select class="ang-input" id="ang-p-scope" data-print-scope>
                        <option value="latest">Batch terakhir</option>
                        <option value="all">Semua kode brosur</option>
                    </select>
                </div>
                <div class="ang-field">
                    <label class="ang-label" for="ang-p-mode">Kode per lembar</label>
                    <select class="ang-input" id="ang-p-mode" data-print-mode>
                        <option value="unique">Satu kode per voucher ({{ count($slots) }} kode)</option>
                        <option value="same">Satu kode di semua voucher</option>
                    </select>
                </div>

                <p class="ang-print-summary" data-print-summary aria-live="polite">Memuat data...</p>
                <p class="ang-field-note" data-print-note hidden></p>

                <progress class="ang-progress" data-print-progress value="0" max="1" hidden></progress>
                <p class="ang-field-note" data-print-progress-text aria-live="polite" hidden></p>
                <span class="ang-error" data-print-error role="alert" hidden></span>
            </div>

            <figure class="ang-print-preview">
                <canvas data-print-canvas width="1" height="1" aria-label="Pratinjau lembar pertama"></canvas>
                <figcaption>Pratinjau lembar 1</figcaption>
            </figure>
        </div>
    </div>

    <div class="ang-modal-foot">
        <button type="button" class="ang-btn" data-ang-close>Tutup</button>
        <button type="button" class="ang-btn" data-print-cancel hidden>Batalkan</button>
        <button type="button" class="ang-btn ang-btn-primary" data-print-start disabled>Buat &amp; unduh ZIP</button>
    </div>
</dialog>
