{{-- Konfirmasi "Tandai USED". Dibuka tombol dengan:
     data-ang-modal="ang-redeem-modal" data-redeem-action="{{ route(...) }}" data-redeem-code="BRO-XXXXXX" --}}
<dialog id="ang-redeem-modal" class="ang-modal ang-confirm" aria-labelledby="ang-redeem-title">
    <form method="POST" action="#">
        @csrf
        @method('PATCH')
        <div class="ang-modal-head">
            <div>
                <h2 class="ang-modal-title" id="ang-redeem-title">Tandai voucher sebagai USED?</h2>
                <p class="ang-modal-sub">Lakukan setelah transaksi dine-in selesai.</p>
            </div>
            <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="ang-modal-body">
            <span class="ang-mono ang-confirm-code" data-redeem-code>-</span>
            <p>Voucher yang sudah USED tidak dapat dipakai kembali.</p>
            <p class="ang-muted-text">Tindakan ini tidak bisa dibatalkan dari halaman ini.</p>
        </div>
        <div class="ang-modal-foot">
            <button type="button" class="ang-btn" data-ang-close>Batal</button>
            <button type="submit" class="ang-btn ang-btn-primary">Ya, tandai USED</button>
        </div>
    </form>
</dialog>
