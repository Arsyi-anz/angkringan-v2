{{-- Konfirmasi "KLAIM" hampers. Dibuka tombol dengan:
     data-ang-modal="ang-claim-modal" data-claim-action="{{ route('admin.hampers.claim', $customer) }}" data-claim-name="Nama Member" --}}
<dialog id="ang-claim-modal" class="ang-modal ang-confirm" aria-labelledby="ang-claim-title">
    <form method="POST" action="#">
        @csrf
        @method('PATCH')
        <div class="ang-modal-head">
            <div>
                <h2 class="ang-modal-title" id="ang-claim-title">Tandai hampers sebagai KLAIM?</h2>
                <p class="ang-modal-sub">Lakukan setelah hampers diserahkan ke member.</p>
            </div>
            <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="ang-modal-body">
            <span class="ang-mono ang-confirm-code" data-claim-code>-</span>
            <p>Hampers yang sudah KLAIM tidak dapat ditandai ulang.</p>
            <p class="ang-muted-text">Kode klaim dibuat otomatis jika belum ada. Tindakan ini tidak bisa dibatalkan dari halaman ini.</p>
        </div>
        <div class="ang-modal-foot">
            <button type="button" class="ang-btn" data-ang-close>Batal</button>
            <button type="submit" class="ang-btn ang-btn-primary">Ya, tandai KLAIM</button>
        </div>
    </form>
</dialog>
