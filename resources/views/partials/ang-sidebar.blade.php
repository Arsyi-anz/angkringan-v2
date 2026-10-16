@php
    // Ganti nilai 'route' / 'match' sesuai nama route di proyekmu.
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.7 3.2 2.3 3.5 5.2"/>',
        'ticket'    => '<path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6z"/><path d="M14 5v14" stroke-dasharray="2 3"/>',
        'check'     => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16 9.5"/>',
        'store'     => '<path d="M4 10v10h16V10"/><path d="M3 10l1.5-5h15L21 10c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3z"/>',
        'gift'      => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8h14v-8M12 8v12M12 8c-1.5-4-5-4-5-1.8C7 8 9 8 12 8zm0 0c1.5-4 5-4 5-1.8C17 8 15 8 12 8z"/>',
        'sliders'   => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
    ];

    $menu = [
        'Workspace' => [
            ['Dashboard',            'dashboard', 'admin.dashboard',         'admin.dashboard'],
            ['Data Customer',        'users',     'admin.customers.index',   'admin.customers.*'],
            ['Manajemen Voucher',    'ticket',    'admin.vouchers.index',    'admin.vouchers.index'],
            ['Validasi Voucher',     'check',     'admin.vouchers.validate', 'admin.vouchers.validate'],
            ['Kunjungan Dine-in',    'store',     'admin.visits.index',      'admin.visits.*'],
            ['Birthday & Hampers',   'gift',      'admin.birthday.index',    'admin.birthday.*'],
        ],
        'Sistem' => [
            ['Pengaturan Program',   'sliders',   'admin.settings.edit',     'admin.settings.*'],
        ],
    ];
@endphp

<aside class="ang-sidebar" aria-label="Menu utama">
    <div class="ang-brand">
        <img src="{{ asset('images/logo.png') }}" class="ang-brand-logo" alt="Angkringan Hotber">
        <span>
            <span class="ang-brand-name">Angkringan Hotber</span>
            <span class="ang-brand-sub">Loyalty console</span>
        </span>
    </div>

    <nav class="ang-nav">
        @foreach ($menu as $group => $items)
            <p class="ang-nav-group">{{ $group }}</p>
            <ul class="ang-nav-list">
                @foreach ($items as [$label, $icon, $route, $match])
                    <li>
                        <a href="{{ Route::has($route) ? route($route) : '#' }}"
                           class="ang-nav-link {{ request()->routeIs($match) ? 'is-active' : '' }}"
                           @if (request()->routeIs($match)) aria-current="page" @endif>
                            <svg class="ang-ico" viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$icon] !!}</svg>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
