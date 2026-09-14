{{-- ============================================================
     Sidebar Super Admin Yayasan.
     Menu "Unit Sekolah" mengarah ke DETAIL INVENTARIS tiap unit
     (tetap di konteks Yayasan, bukan masuk ke dashboard sekolah).
     ============================================================ --}}
@php
    $u = auth()->user();

    $sidebarUnits = \App\Models\Unit::orderBy('id')->get();
    $qtyPerUnit = \App\Models\Asset::selectRaw('unit_id, SUM(total_qty) as qty')
        ->groupBy('unit_id')->pluck('qty', 'unit_id');

    $laporanPending = \App\Models\Report::where('status', 'pending')->count();
    $laporanAktif   = \App\Models\Report::whereIn('status', ['pending', 'proses'])->count();
    $laporanSelesai = \App\Models\Report::where('status', 'selesai')->count();
    $activeUnitId   = (int) request()->route('unit');
@endphp

<aside class="sidebar sidebar--yayasan" id="sidebar">

    {{-- ── BRAND ── --}}
    <div class="sidebar-brand">
        <img src="{{ asset('image/logo.png') }}" alt="Logo" class="sidebar-brand-img" style="width:34px; height:34px; object-fit:contain; flex-shrink:0;">
        <div class="brand-text">
            <span class="brand-name">SarPras</span>
            <span class="brand-unit brand-unit--yayasan">Yayasan Husnul Abror</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Perkecil / perbesar sidebar">
            <i data-lucide="chevron-left" style="width:15px;height:15px;"></i>
        </button>
    </div>

    {{-- ── USER ── --}}
    <div class="sidebar-user">
        <div class="user-avatar user-avatar--yayasan">{{ $u?->initials() ?? 'YA' }}</div>
        <div class="user-info">
            <span class="user-name">{{ $u->name ?? 'Admin Yayasan' }}</span>
            <span class="user-role">Super Admin · Yayasan</span>
        </div>
    </div>

    {{-- ── NAV ── --}}
    <nav class="sidebar-nav">

        <a href="{{ route('dashboard.yayasan') }}" class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span>
            <span class="nav-label">Dashboard Yayasan</span>
        </a>

        {{-- ── UNIT SEKOLAH ── --}}
        <div class="nav-divider"><span>Unit Sekolah</span></div>

        <a href="{{ route('unit.index') }}" class="nav-item {{ request()->routeIs('unit.index') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="building-2"></i></span>
            <span class="nav-label">Rekap Semua Unit</span>
        </a>

        @foreach($sidebarUnits as $unit)
            <a href="{{ route('unit.show', $unit->id) }}"
               class="nav-item {{ request()->routeIs('unit.show') && $activeUnitId === $unit->id ? 'active' : '' }}"
               title="Detail inventaris {{ $unit->nama }}">
                <span class="nav-icon"><i data-lucide="{{ $unit->icon() }}"></i></span>
                <span class="nav-label">Unit {{ $unit->label() }} ({{ $unit->jenjang() }})</span>
                <span class="nav-badge nav-badge--muted">{{ number_format((int) ($qtyPerUnit[$unit->id] ?? 0)) }}</span>
            </a>
        @endforeach

        {{-- ── LAPORAN & AUDIT ── --}}
        <div class="nav-divider"><span>Laporan & Audit</span></div>

        <a href="{{ route('laporan.index') }}" class="nav-item {{ request()->routeIs('laporan.index') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="file-bar-chart"></i></span>
            <span class="nav-label">Laporan & Audit Global</span>
            @if($laporanPending > 0)
                <span class="nav-badge nav-badge--alert">{{ $laporanPending }}</span>
            @endif
        </a>

        <a href="{{ route('laporan.selesai') }}" class="nav-item {{ request()->routeIs('laporan.selesai') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="check-circle-2"></i></span>
            <span class="nav-label">Laporan Terverifikasi</span>
            @if($laporanSelesai > 0)
                <span class="nav-badge">{{ $laporanSelesai }}</span>
            @endif
        </a>

        <a href="{{ route('laporan.masuk') }}" class="nav-item {{ request()->routeIs('laporan.masuk') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="arrow-down-left-square"></i></span>
            <span class="nav-label">Audit Barang Masuk</span>
        </a>

        <a href="{{ route('laporan.keluar') }}" class="nav-item {{ request()->routeIs('laporan.keluar') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="arrow-up-right-square"></i></span>
            <span class="nav-label">Audit Barang Keluar</span>
        </a>

        {{-- ── SISTEM & USER ── --}}
        <div class="nav-divider"><span>Sistem & User</span></div>

        <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="users"></i></span>
            <span class="nav-label">Pengaturan Akun & User</span>
        </a>

        <a href="{{ route('notifikasi.index') }}" class="nav-item {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="bell"></i></span>
            <span class="nav-label">Notifikasi</span>
        </a>

        <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="user-circle"></i></span>
            <span class="nav-label">Pengaturan Akun Saya</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-item nav-item--logout">
                <span class="nav-icon"><i data-lucide="log-out"></i></span>
                <span class="nav-label">Keluar</span>
            </button>
        </form>

    </nav>
</aside>
