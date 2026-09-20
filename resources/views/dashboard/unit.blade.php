@extends('layouts.admin')

@php
    $tema = [
        'grad'   => 'linear-gradient(135deg, #152C48 0%, #1E3A5F 55%, #2A4F80 100%)',
        'accent' => '#1E3A5F',
        'soft'   => 'rgba(30, 58, 95, 0.08)',
        'icon'   => match ($kodeUnit ?? 'mi') {
            'mts'   => 'graduation-cap',
            'smk'   => 'cpu',
            default => 'school',
        },
    ];

    $tipeIcon = [
        'kelas' => 'door-open', 'lab' => 'monitor', 'bengkel' => 'wrench',
        'perpustakaan' => 'book-open', 'kantor' => 'briefcase', 'aula' => 'projector',
        'uks' => 'heart-pulse', 'lainnya' => 'map-pin',
    ];
@endphp

@section('page-title', 'Dashboard Unit ' . ($unit?->label() ?? 'Sekolah'))
@section('page-icon', $tema['icon'])

@section('styles')
<style>
    .unit-hero {
        background: {{ $tema['grad'] }};
        border-radius: 18px; padding: clamp(18px, 3vw, 28px);
        color: #fff; margin-bottom: 22px;
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; flex-wrap: wrap;
        box-shadow: 0 12px 26px -10px rgba(15,23,42,.45);
    }
    .unit-hero h2 { font-size: clamp(1.15rem, 3vw, 1.5rem); font-weight: 800; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
    .unit-hero p { font-size: .85rem; color: rgba(255,255,255,.75); margin-top: 5px; }
    .hero-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.25);
        padding: 5px 12px; border-radius: 100px; font-size: .74rem; font-weight: 700;
    }
    .room-card {
        display: flex; flex-direction: column; justify-content: space-between; gap: 12px;
        background: #fff; border: 1px solid var(--border); border-radius: var(--radius);
        padding: 15px 16px; text-decoration: none; color: inherit;
        transition: transform .18s, box-shadow .18s, border-color .18s;
        box-shadow: var(--shadow-sm);
    }
    .room-card:hover { transform: translateY(-3px); border-color: {{ $tema['accent'] }}; box-shadow: var(--shadow); }
    .room-name { font-weight: 800; font-size: .9rem; color: var(--navy); display: flex; align-items: center; gap: 7px; }
    .room-meta { font-size: .76rem; color: var(--gray); display: flex; gap: 10px; flex-wrap: wrap; margin-top: 4px; }
    .room-foot { display: flex; align-items: center; justify-content: space-between; font-size: .74rem; font-weight: 700; }
    .act-row {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 11px 14px; background: var(--slate); border: 1px solid var(--border);
        border-radius: 11px;
    }
    .act-row + .act-row { margin-top: 9px; }
</style>
@endsection

@section('content')

{{-- ── HERO ── --}}
<div class="unit-hero">
    <div>
        <h2><i data-lucide="{{ $tema['icon'] }}" style="width:24px;height:24px;"></i> Dashboard Inventaris {{ $unit?->label() ?? 'Unit' }}</h2>
        <p>
            {{ $unit->nama ?? 'Unit Sekolah' }} · Kepala Unit: {{ $unit->kepala_unit ?? '-' }} ·
            {{ $stats['total_ruangan'] }} ruangan terdaftar
        </p>
    </div>
    <div style="display:flex;gap:9px;flex-wrap:wrap;align-items:center;">
        <span class="hero-chip"><i data-lucide="shield-check" style="width:13px;height:13px;"></i> {{ auth()->user()->roleLabel() }}</span>
        <a href="{{ route('laporan.create') }}" class="btn btn-ghost btn-sm"><i data-lucide="file-plus"></i> Buat Laporan</a>
        <a href="{{ route('aset.scan') }}" class="btn btn-ghost btn-sm"><i data-lucide="scan-line"></i> Scan QR</a>
    </div>
</div>

{{-- ── METRIK ── --}}
<div class="grid grid-stats" style="margin-bottom:22px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Aset</span>
            <span class="stat-ico" style="background:{{ $tema['soft'] }};color:{{ $tema['accent'] }};"><i data-lucide="package"></i></span>
        </div>
        <div class="stat-val">{{ number_format($stats['total_aset']) }} <small>unit</small></div>
        <div class="stat-sub">{{ $stats['total_jenis'] }} jenis barang · {{ $stats['total_nilai'] }}</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Kondisi Baik</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="check-circle-2"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ number_format($stats['aset_baik']) }} <small>unit</small></div>
        <div class="stat-sub">{{ $stats['persen_baik'] }}% dari total aset unit</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Perlu Perbaikan</span>
            <span class="stat-ico" style="background:#FEF08A;color:#B45309;"><i data-lucide="wrench"></i></span>
        </div>
        <div class="stat-val" style="color:#B45309;">{{ number_format($stats['aset_rusak_ringan']) }} <small>unit</small></div>
        <div class="stat-sub">Rusak ringan</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Rusak Berat</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="alert-octagon"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ number_format($stats['aset_rusak_berat']) }} <small>unit</small></div>
        <div class="stat-sub">Perlu penggantian</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Sedang Dipinjam</span>
            <span class="stat-ico" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="handshake"></i></span>
        </div>
        <div class="stat-val" style="color:#6B21A8;">{{ number_format($stats['dipinjam']) }} <small>unit</small></div>
        <div class="stat-sub">Tercatat pada Barang Keluar</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Status Pelaporan</span>
            <span class="stat-ico" style="background:#EFF6FF;color:#2563EB;"><i data-lucide="file-search"></i></span>
        </div>
        <div class="stat-val">{{ $stats['laporan_pending'] + $stats['laporan_proses'] }} <small>aktif</small></div>
        <div class="stat-sub">
            {{ $stats['laporan_pending'] }} menunggu verifikasi ·
            {{ $stats['laporan_selesai'] }} selesai
        </div>
    </div>
</div>

{{-- ── AKSI CEPAT ── --}}
<div class="grid grid-cards" style="margin-bottom:22px;">
    <a href="{{ route('laporan.create') }}" class="room-card">
        <div class="room-name"><i data-lucide="file-plus" style="color:#DC2626;"></i> Buat Laporan Kerusakan</div>
        <div class="room-meta">Ajukan kerusakan barang ke Admin Yayasan</div>
    </a>
    <a href="{{ route('laporan.keluar') }}" class="room-card">
        <div class="room-name"><i data-lucide="arrow-up-right-square" style="color:#EA580C;"></i> Barang Keluar</div>
        <div class="room-meta">Penggantian barang rusak & peminjaman barang</div>
    </a>
    <a href="{{ route('aset.cetak-label') }}" class="room-card">
        <div class="room-name"><i data-lucide="printer" style="color:#2563EB;"></i> Cetak Label QR</div>
        <div class="room-meta">Pilih ruangan & barang yang akan dicetak</div>
    </a>
</div>

{{-- ── DAFTAR RUANGAN ── --}}
<div class="page-head" style="margin-bottom:12px;">
    <div>
        <h1 style="font-size:1.05rem;"><i data-lucide="door-open"></i> Ruang Kelas & Fasilitas {{ $unit?->label() }}</h1>
        <p>Pilih ruangan untuk mengelola daftar barang & QR Code-nya</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('locations.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="settings-2"></i> Kelola Ruangan</a>
    </div>
</div>

<div class="grid grid-cards" style="margin-bottom:24px;">
    @forelse($ruanganList as $r)
        <a href="{{ route('ruangan.show', ['ruangan' => $r['id']]) }}" class="room-card">
            <div>
                <div class="room-name">
                    <i data-lucide="{{ $tipeIcon[$r['tipe']] ?? 'map-pin' }}" style="color:{{ $tema['accent'] }};"></i>
                    {{ $r['nama'] }}
                </div>
                <div class="room-meta">
                    <span><i data-lucide="package" style="width:12px;height:12px;"></i> {{ number_format($r['jumlah']) }} unit</span>
                    <span><i data-lucide="layers" style="width:12px;height:12px;"></i> {{ $r['jenis'] }} jenis</span>
                </div>
            </div>
            <div class="room-foot">
                @if($r['kondisi'] === 'Baik')
                    <span class="badge badge-green">● Baik</span>
                @elseif($r['kondisi'] === 'Butuh Perbaikan')
                    <span class="badge badge-amber">● {{ $r['rusak_ringan'] }} rusak ringan</span>
                @else
                    <span class="badge badge-red">● {{ $r['rusak_berat'] }} rusak berat</span>
                @endif
                <span style="color:{{ $tema['accent'] }};">Kelola →</span>
            </div>
        </a>
    @empty
        <div class="card card-pad" style="grid-column:1/-1;">
            <div class="empty-state">
                <i data-lucide="door-closed" style="width:36px;height:36px;stroke-width:1.4;"></i>
                <strong>Belum ada ruangan terdaftar</strong>
                <p>Tambahkan ruangan pada menu <a href="{{ route('locations.index') }}">Master Data → Lokasi Ruangan</a>.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="grid grid-2">
    {{-- ── STATUS PELAPORAN TERBARU ── --}}
    <div class="card">
        <div class="card-head">
            <h3><i data-lucide="clipboard-list"></i> Status Laporan Terbaru</h3>
            <a href="{{ route('laporan.index') }}" class="muted" style="text-decoration:none;color:var(--blue);">Cek semua →</a>
        </div>
        <div class="card-pad">
            @forelse($laporanTerbaru as $lap)
                <div class="act-row">
                    <div style="min-width:0;">
                        <div style="font-size:.85rem;font-weight:700;color:var(--navy);">{{ $lap->judul }}</div>
                        <div style="font-size:.74rem;color:var(--gray);">
                            {{ $lap->jenisLabel() }} · {{ $lap->created_at?->format('d M Y') }}
                        </div>
                    </div>
                    @if($lap->isSelesai())
                        <span class="badge {{ $lap->isRejected() ? 'badge-red' : 'badge-green' }}">
                            {{ $lap->isRejected() ? 'Ditolak' : 'Disetujui' }}
                        </span>
                    @elseif($lap->isProses())
                        <span class="badge badge-blue">Diproses</span>
                    @else
                        <span class="badge badge-amber">Menunggu</span>
                    @endif
                </div>
            @empty
                <div class="empty-state">
                    <strong>Belum ada laporan</strong>
                    <p>Laporan kerusakan, penggantian, dan peminjaman akan tampil di sini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── LOG AKTIVITAS ── --}}
    <div class="card">
        <div class="card-head">
            <h3><i data-lucide="history"></i> Log Aktivitas Terbaru</h3>
        </div>
        <div class="card-pad">
            @forelse($recentActivities as $act)
                <div class="act-row">
                    <div style="display:flex;align-items:center;gap:11px;min-width:0;">
                        <span style="color:{{ $tema['accent'] }};"><i data-lucide="{{ $act['icon'] }}"></i></span>
                        <div style="min-width:0;">
                            <div style="font-size:.84rem;font-weight:600;color:var(--navy);">{{ $act['desc'] }}</div>
                            <div style="font-size:.73rem;color:var(--gray);">Oleh {{ $act['user'] }}</div>
                        </div>
                    </div>
                    <span style="font-size:.73rem;color:var(--gray);white-space:nowrap;">{{ $act['time'] }}</span>
                </div>
            @empty
                <div class="empty-state">
                    <strong>Belum ada aktivitas</strong>
                    <p>Mutasi barang & pelaporan terbaru akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
