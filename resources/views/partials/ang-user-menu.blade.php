{{-- Menu pengguna di topbar (kanan atas). Di-include di dalam <header class="ang-topbar"> --}}
@php $umName = auth()->user()->name ?? 'Administrator'; @endphp
<div class="ang-um" data-um>
    <button type="button" class="ang-um-btn" data-um-btn aria-expanded="false" aria-controls="ang-um-menu">
        <span class="ang-um-avatar">{{ strtoupper(mb_substr($umName, 0, 2)) }}</span>
        <span class="ang-um-text">
            <span class="ang-um-name">{{ $umName }}</span>
            <span class="ang-um-role">Admin outlet</span>
        </span>
        <svg class="ang-um-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div class="ang-um-menu" id="ang-um-menu" data-um-menu hidden>
        <div class="ang-um-head">
            <strong>{{ $umName }}</strong>
            <span>Admin outlet</span>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="ang-um-item">
                <svg class="ang-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M16 8l4 4-4 4M20 12H9"/></svg>
                Keluar
            </button>
        </form>
    </div>
</div>
