{{-- Modal Pratinjau pesan ulang tahun. Dibuka lewat data-ang-modal="ang-message-modal".
     Ubah teks template di bawah sesuai pesan yang benar-benar dipakai. --}}
<dialog id="ang-message-modal" class="ang-modal ang-modal-wide" aria-labelledby="ang-message-modal-title">
    <div class="ang-modal-head">
        <div>
            <h2 class="ang-modal-title" id="ang-message-modal-title">Pratinjau pesan ulang tahun</h2>
            <p class="ang-modal-sub">Template pesan berdasarkan kelayakan customer.</p>
        </div>
        <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <div class="ang-modal-body">
        <div class="ang-msg">
            <p class="ang-msg-title">Jika belum layak:</p>
            <p>Selamat ulang tahun, <span class="ang-ph">[Nama]</span>! Semoga sehat dan bahagia selalu. Terima kasih sudah menjadi bagian dari Angkringan.</p>
        </div>
        <div class="ang-msg">
            <p class="ang-msg-title">Jika layak dan kuota tersedia:</p>
            <p>Selamat ulang tahun, <span class="ang-ph">[Nama]</span>! Ada hampers spesial dari Angkringan untuk Anda. Silakan datang ke outlet dan tunjukkan chat WhatsApp ini kepada kasir untuk klaim hampers. Selamat menikmati hari istimewa Anda!</p>
        </div>
    </div>

    <div class="ang-modal-foot">
        <button type="button" class="ang-btn ang-btn-primary" data-ang-close>Tutup</button>
    </div>
</dialog>
