{{-- Halaman daftar: tombol Sebelumnya/Berikutnya memakai kelas ang-pager (ang-customer.css). --}}
@if (method_exists($pages ?? null, 'hasPages') && $pages->hasPages())
    @php $pg = $pages->withQueryString(); @endphp
    <nav class="ang-pager" aria-label="Halaman">
        <span>Halaman {{ $pg->currentPage() }} dari {{ $pg->lastPage() }}</span>
        <span class="ang-pager-nav">
            <a class="ang-btn ang-btn-sm {{ $pg->onFirstPage() ? 'ang-link-off' : '' }}" href="{{ $pg->previousPageUrl() ?? '#' }}" @if ($pg->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>
            <a class="ang-btn ang-btn-sm {{ $pg->hasMorePages() ? '' : 'ang-link-off' }}" href="{{ $pg->nextPageUrl() ?? '#' }}" @unless ($pg->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>Berikutnya</a>
        </span>
    </nav>
@endif
