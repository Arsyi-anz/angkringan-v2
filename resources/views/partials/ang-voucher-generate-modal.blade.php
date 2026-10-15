{{-- Modal Generate voucher. Dibuka tombol dengan data-ang-modal="ang-gen-modal" + data-gen-source/name/format/max.
     Memakai $channels dan $remainingBy dari halaman induk. --}}
@php
    $oldSource = old('source');
    $oldChan   = collect($channels ?? [])->firstWhere('code', $oldSource);
    $oldMax    = $oldSource ? ($remainingBy[$oldSource] ?? 0) : 0;
@endphp
<dialog id="ang-gen-modal" class="ang-modal" aria-labelledby="ang-gen-title"
        @if ($errors->any() && old('_ang_form') === 'voucher-generate') data-ang-open @endif>
    <form method="POST" action="{{ Route::has('admin.vouchers.generate') ? route('admin.vouchers.generate') : '#' }}" novalidate>
        @csrf
        <input type="hidden" name="_ang_form" value="voucher-generate">
        <input type="hidden" name="source" value="{{ $oldSource }}">

        <div class="ang-modal-head">
            <div>
                <h2 class="ang-modal-title" id="ang-gen-title">Generate voucher</h2>
                <p class="ang-modal-sub">Kode dibuat acak, unik, dan hanya berlaku sekali.</p>
            </div>
            <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="ang-modal-body">
            <p class="ang-gen-source">
                <strong data-gen-name>{{ $oldChan['name'] ?? '' }}</strong>
                <span class="ang-mono" data-gen-format>{{ $oldSource ? $oldSource.'-XXXXXX' : '' }}</span>
            </p>
            <div class="ang-field">
                <label class="ang-label" for="ang-gen-qty">Jumlah kode</label>
                <input class="ang-input @error('quantity') is-invalid @enderror" id="ang-gen-qty" name="quantity" type="number"
                       min="1" max="{{ $oldMax }}" step="1" inputmode="numeric" value="{{ old('quantity') }}" placeholder="Contoh: 100" required>
                <span class="ang-field-note">Sisa kuota: <strong data-gen-remaining>{{ number_format($oldMax, 0, ',', '.') }}</strong> kode</span>
                <span class="ang-error" data-gen-error @if (!$errors->has('quantity')) hidden @endif>{{ $errors->first('quantity') }}</span>
            </div>
        </div>

        <div class="ang-modal-foot">
            <button type="button" class="ang-btn" data-ang-close>Batal</button>
            <button type="submit" class="ang-btn ang-btn-primary">Generate</button>
        </div>
    </form>
</dialog>
