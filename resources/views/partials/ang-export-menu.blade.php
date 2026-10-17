{{-- Tombol Export dropdown: pilihan CSV & PDF. Pakai: @include('partials.ang-export-menu', ['csvUrl' => ..., 'pdfUrl' => ...]) --}}
@php
    $csvUrl = $csvUrl ?? '#';
    $pdfUrl = $pdfUrl ?? '#';
@endphp
<div class="ang-dd" data-ang-dd>
    <button type="button" class="ang-btn ang-dd-btn" data-ang-dd-btn aria-expanded="false" aria-controls="ang-export-menu">
        <svg class="ang-ico" viewBox="0 0 24 24"><path d="M12 4v11M7.5 11 12 15.5 16.5 11M5 20h14"/></svg>
        Export
        <svg class="ang-btn-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <div class="ang-dd-menu" id="ang-export-menu" data-ang-dd-menu hidden>
        <a class="ang-dd-item" href="{{ $csvUrl }}">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>
            CSV
        </a>
        <a class="ang-dd-item" href="{{ $pdfUrl }}">
            <svg class="ang-ico" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>
            PDF
        </a>
    </div>
</div>
