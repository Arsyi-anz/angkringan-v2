{{-- Modal Catat kunjungan dine-in. Dibuka lewat data-ang-modal="ang-visit-modal"
     dan data-customer-id="{{ $c->id_customer }}" untuk mengisi customer otomatis. --}}
@php
    // Kirim $customerOptions dari controller (semua customer, tanpa filter pencarian).
    $options = $customerOptions ?? $customers ?? collect();
@endphp

<dialog id="ang-visit-modal" class="ang-modal" aria-labelledby="ang-visit-modal-title"
        @if ($errors->any() && old('_ang_form') === 'visit') data-ang-open @endif>
    <form method="POST" action="{{ Route::has('admin.visits.store') ? route('admin.visits.store') : '#' }}" novalidate>
        @csrf
        <input type="hidden" name="_ang_form" value="visit">

        <div class="ang-modal-head">
            <div>
                <h2 class="ang-modal-title" id="ang-visit-modal-title">Catat kunjungan dine-in</h2>
                <p class="ang-modal-sub">Kunjungan take-away tidak boleh dicatat. Maksimal satu kunjungan per member per hari.</p>
            </div>
            <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="ang-modal-body">
            <div class="ang-field">
                <label class="ang-label" for="ang-v-search">Customer <span class="ang-req">*</span></label>
                <div class="ang-combo">
                    <input type="hidden" name="customer_id" id="ang-v-customer" value="{{ old('customer_id') }}">
                    <input class="ang-input @error('customer_id') is-invalid @enderror" id="ang-v-search" type="text"
                           placeholder="Cari nama atau nomor WA" autocomplete="off"
                           role="combobox" aria-expanded="false" aria-controls="ang-v-list" aria-autocomplete="list">
                    <svg class="ang-ico ang-combo-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>

                    <ul class="ang-combo-list" id="ang-v-list" role="listbox" hidden>
                        @foreach ($options as $o)
                        <li class="ang-combo-opt" role="option" id="ang-v-opt-{{ $o->id_customer }}" aria-selected="false"
                            data-id="{{ $o->id_customer }}"
                                data-name="{{ $o->name }}"
                                data-wa-raw="{{ $o->whatsapp }}"
                                data-label="{{ $o->name }} · {{ $o->whatsapp }}">
                                <span class="ang-combo-name">{{ $o->name }}</span>
                                <span class="ang-combo-wa">{{ $o->whatsapp }}</span>
                            </li>
                        @endforeach
                        <li class="ang-combo-empty" hidden>Customer tidak ditemukan</li>
                    </ul>
                </div>
                <span class="ang-error" data-ang-combo-error @if (!$errors->has('customer_id')) hidden @endif>
                    {{ $errors->first('customer_id') ?: 'Pilih customer dari daftar.' }}
                </span>
            </div>

            <div class="ang-field">
                <label class="ang-label" for="ang-v-date">Tanggal kunjungan <span class="ang-req">*</span></label>
                <input class="ang-input @error('visited_at') is-invalid @enderror" id="ang-v-date" name="visited_at"
                       type="date" value="{{ old('visited_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                @error('visited_at')<span class="ang-error">{{ $message }}</span>@enderror
            </div>

            <div class="ang-field">
                <label class="ang-label" for="ang-v-type">Jenis kunjungan</label>
                <div class="ang-lockwrap">
                    <input class="ang-input" id="ang-v-type" type="text" value="Dine-in (wajib)" readonly aria-readonly="true">
                    <svg class="ang-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                </div>
                <input type="hidden" name="visit_type" value="dine_in">
            </div>
        </div>

        <div class="ang-modal-foot">
            <button type="button" class="ang-btn" data-ang-close>Batal</button>
            <button type="submit" class="ang-btn ang-btn-primary">Simpan kunjungan</button>
        </div>
    </form>
</dialog>
