{{-- Modal Tambah / Edit customer. Dibuka lewat tombol dengan data-ang-modal="ang-customer-modal".
     Tombol Edit menyertakan data-edit-* yang dipakai JS untuk mengisi field dan mengganti aksi form. --}}
@php
    $editingId = old('_ang_edit');
    $isEdit = filled($editingId);
@endphp
<dialog id="ang-customer-modal" class="ang-modal" aria-labelledby="ang-customer-modal-title"
        @if ($errors->any() && old('_ang_form') === 'customer') data-ang-open @endif>
    <form method="POST" id="ang-customer-form"
          action="{{ $isEdit ? route('admin.customers.update', $editingId) : route('admin.customers.store') }}"
          data-store-action="{{ route('admin.customers.store') }}"
          data-edit-base="{{ url('admin/customers') }}"
          novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <input type="hidden" name="_ang_form" value="customer">
        <input type="hidden" name="_ang_edit" id="ang-f-edit" value="{{ $editingId }}">

        <div class="ang-modal-head">
            <div>
                <h2 class="ang-modal-title" id="ang-customer-modal-title">{{ $isEdit ? 'Edit customer' : 'Tambah customer' }}</h2>
                <!-- <p class="ang-modal-sub">Nomor WhatsApp harus unik. Tanggal lahir boleh dikosongkan.</p> -->
            </div>
            <button type="button" class="ang-modal-close" data-ang-close aria-label="Tutup">
                <svg class="ang-ico" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="ang-modal-body">
            <div class="ang-field">
                <label class="ang-label" for="ang-f-name">Nama customer <span class="ang-req">*</span></label>
                <input class="ang-input @error('nama') is-invalid @enderror" id="ang-f-name" name="nama"
                       type="text" value="{{ old('nama') }}" placeholder="Nama lengkap" maxlength="50" required autofocus>
                @error('nama')<span class="ang-error">{{ $message }}</span>@enderror
            </div>

            <div class="ang-field">
                <label class="ang-label" for="ang-f-wa">Nomor WhatsApp aktif <span class="ang-req">*</span></label>
                <input class="ang-input @error('no_wa') is-invalid @enderror" id="ang-f-wa" name="no_wa"
                       type="tel" inputmode="tel" value="{{ old('no_wa') }}" placeholder="08xxxxxxxxxx"
                       maxlength="15" required>
                @error('no_wa')<span class="ang-error">{{ $message }}</span>@enderror
            </div>

            <div class="ang-field">
                <label class="ang-label" for="ang-f-birth">Tanggal lahir (opsional)</label>
                <input class="ang-input @error('lahir_tgl') is-invalid @enderror @error('lahir_bln') is-invalid @enderror"
                       id="ang-f-birth" name="birth_date"
                       type="date" value="{{ old('birth_date') }}" max="{{ now()->toDateString() }}">
                @error('lahir_tgl')<span class="ang-error">{{ $message }}</span>@enderror
                @error('lahir_bln')<span class="ang-error">{{ $message }}</span>@enderror
                @error('birth_date')<span class="ang-error">{{ $message }}</span>@enderror
            </div>

            <div class="ang-field">
                <label class="ang-check">
                    <input type="hidden" name="setuju_pesan" value="0">
                    <input type="checkbox" name="setuju_pesan" value="1" @checked(old('setuju_pesan'))>
                    Customer setuju menerima pesan WhatsApp dan info program
                </label>
                <!-- <p class="ang-hint">Persetujuan perlu dicatat dan customer harus memiliki opsi berhenti menerima pesan.</p> -->
                @error('setuju_pesan')<span class="ang-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="ang-modal-foot">
            <button type="button" class="ang-btn" data-ang-close>Batal</button>
            <button type="submit" class="ang-btn ang-btn-primary">{{ $isEdit ? 'Simpan perubahan' : 'Simpan customer' }}</button>
        </div>
    </form>
</dialog>
