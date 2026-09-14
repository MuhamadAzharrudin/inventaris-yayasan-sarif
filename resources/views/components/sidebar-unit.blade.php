{{-- ============================================================
     Sidebar Admin Unit Sekolah (MI / MTS / SMK)
     Daftar ruangan diambil langsung dari master data lokasi unit,
     sehingga penambahan / penghapusan ruangan otomatis tercermin.
     ============================================================ --}}
@php
    $u        = auth()->user();
    $unit     = $u?->unit;
    $unitKode = $u?->unitKode() ?? 'mi';

    $unitIcon = match ($unitKode) {
        'mts'   => 'graduation-cap',
        'smk'   => 'cpu',
        default => 'school',
    };

    $sidebarLocations = \App\Models\Location::withCount('assets')
        ->where('unit_id', $u?->unit_id)
        ->orderByRaw("CASE WHEN tipe = 'kelas' THEN 0 ELSE 1 END")
        ->orderBy('nama')
        ->get();

    $activeSlug = request()->route('ruangan');

    $laporanAktif = \App\Models\Report::where('unit_id', $u?->unit_id)
        ->whereIn('status', ['pending', 'proses'])->count();
    $laporanSelesai = \App\Models\Report::where('unit_id', $u?->unit_id)
        ->where('status', 'selesai')->count();
    $pinjamAktif = \App\Models\AssetMutation::where('unit_id', $u?->unit_id)
        ->where('jenis', 'peminjaman')->where('status_pinjam', 'dipinjam')->count();

    $tipeIcon = [
        'kelas'        => 'door-open',
        'lab'          => 'monitor',
        'bengkel'      => 'wrench',
        'perpustakaan' => 'book-open',
        'kantor'       => 'briefcase',
        'aula'         => 'projector',
        'uks'          => 'heart-pulse',
        'lainnya'      => 'map-pin',
    ];
@endphp

<aside class="sidebar" id="sidebar">

    {{-- ── BRAND ── --}}
    <div class="sidebar-brand">
        <img src="{{ asset('image/logo.png') }}" alt="Logo" class="sidebar-brand-img" style="width:34px; height:34px; object-fit:contain; flex-shrink:0;">
        <div class="brand-text">
            <span class="brand-name">SarPras</span>
            <span class="brand-unit">{{ $unit->nama ?? 'Unit Sekolah' }}</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Perkecil / perbesar sidebar">
            <i data-lucide="chevron-left" style="width:15px;height:15px;"></i>
        </button>
    </div>

    {{-- ── USER ── --}}
    <div class="sidebar-user">
        <div class="user-avatar">{{ $u?->initials() ?? 'AD' }}</div>
        <div class="user-info">
            <span class="user-name">{{ $u->name ?? 'Admin Unit' }}</span>
            <span class="user-role">{{ $u?->roleLabel() ?? 'Admin Unit' }}</span>
        </div>
    </div>

    {{-- ── NAV ── --}}
    <nav class="sidebar-nav">

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span>
            <span class="nav-label">Dashboard</span>
        </a>

        {{-- ── RUANG KELAS & FASILITAS ── --}}
        <div class="nav-group {{ request()->routeIs('ruangan.*') || request()->routeIs('aset.create') ? 'open' : '' }}" id="groupRuangan">
            <button class="nav-group-trigger" type="button" onclick="toggleGroup('groupRuangan')">
                <span class="nav-icon"><i data-lucide="school"></i></span>
                <span class="nav-label">Ruang & Fasilitas</span>
                <span class="nav-badge nav-badge--muted">{{ $sidebarLocations->count() }}</span>
                <span class="nav-arrow"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></span>
            </button>
            <div class="nav-group-items">
                @forelse($sidebarLocations as $loc)
                    <a href="{{ route('ruangan.show', ['ruangan' => $loc->slug]) }}"
                       class="nav-sub-item {{ $activeSlug === $loc->slug ? 'active' : '' }}"
                       title="Daftar barang {{ $loc->nama }}">
                        <i data-lucide="{{ $tipeIcon[$loc->tipe] ?? 'map-pin' }}" style="width:13px;height:13px;"></i>
                        <span class="nav-label">{{ $loc->nama }}</span>
                        <span class="nav-sub-meta">{{ $loc->assets_count }}</span>
                    </a>
                @empty
                    <a href="{{ route('locations.index') }}" class="nav-sub-item">
                        <i data-lucide="plus" style="width:13px;height:13px;"></i>
                        <span class="nav-label">Tambah ruangan dulu</span>
                    </a>
                @endforelse
            </div>
        </div>

        {{-- ── MASTER DATA ── --}}
        <div class="nav-divider"><span>Master Data</span></div>

        <a href="{{ route('aset.scan') }}" class="nav-item {{ request()->routeIs('aset.scan') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="scan-line"></i></span>
            <span class="nav-label">Scan QR Code</span>
        </a>

        <a href="{{ route('aset.cetak-label') }}" class="nav-item {{ request()->routeIs('aset.cetak-label*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="printer"></i></span>
            <span class="nav-label">Cetak Label QR</span>
        </a>

        <a href="{{ route('categories.index') }}" class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="tag"></i></span>
            <span class="nav-label">Kategori Barang</span>
        </a>

        <a href="{{ route('merek.index') }}" class="nav-item {{ request()->routeIs('merek.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="bookmark"></i></span>
            <span class="nav-label">Merek / Brand</span>
        </a>

        <a href="{{ route('locations.index') }}" class="nav-item {{ request()->routeIs('locations.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="map-pin"></i></span>
            <span class="nav-label">Lokasi Ruangan</span>
        </a>

        {{-- ── PELAPORAN & MUTASI ── --}}
        <div class="nav-divider"><span>Pelaporan & Mutasi</span></div>

        <a href="{{ route('laporan.create') }}" class="nav-item {{ request()->routeIs('laporan.create') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="file-plus"></i></span>
            <span class="nav-label">Tambah Laporan</span>
        </a>

        <a href="{{ route('laporan.masuk') }}" class="nav-item {{ request()->routeIs('laporan.masuk') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="arrow-down-left-square"></i></span>
            <span class="nav-label">Barang Masuk</span>
        </a>

        <a href="{{ route('laporan.keluar') }}" class="nav-item {{ request()->routeIs('laporan.keluar') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="arrow-up-right-square"></i></span>
            <span class="nav-label">Barang Keluar</span>
            @if($pinjamAktif > 0)
                <span class="nav-badge nav-badge--muted">{{ $pinjamAktif }}</span>
            @endif
        </a>

        <a href="{{ route('laporan.index') }}" class="nav-item {{ request()->routeIs('laporan.index') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="file-search"></i></span>
            <span class="nav-label">Cek Pelaporan</span>
            @if($laporanAktif > 0)
                <span class="nav-badge nav-badge--alert">{{ $laporanAktif }}</span>
            @endif
        </a>

        <a href="{{ route('laporan.selesai') }}" class="nav-item {{ request()->routeIs('laporan.selesai') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="check-circle-2"></i></span>
            <span class="nav-label">Pelaporan Selesai</span>
            @if($laporanSelesai > 0)
                <span class="nav-badge">{{ $laporanSelesai }}</span>
            @endif
        </a>

        {{-- ── AKUN ── --}}
        <div class="nav-divider"><span>Akun</span></div>

        <a href="{{ route('notifikasi.index') }}" class="nav-item {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="bell"></i></span>
            <span class="nav-label">Notifikasi</span>
        </a>

        <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="nav-icon"><i data-lucide="user-circle"></i></span>
            <span class="nav-label">Pengaturan Akun</span>
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
