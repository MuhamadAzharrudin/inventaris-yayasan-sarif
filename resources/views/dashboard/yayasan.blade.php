@extends('layouts.admin')

@section('page-title', 'Dashboard Yayasan')
@section('page-icon', 'landmark')

@section('styles')
<style>
    .yaya-hero {
        background: linear-gradient(135deg, #152C48 0%, #1E3A5F 55%, #2A4F80 100%);
        border-radius: 18px; padding: clamp(18px, 3vw, 28px); color: #fff;
        margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between;
        gap: 16px; flex-wrap: wrap; box-shadow: 0 12px 26px -10px rgba(15,23,42,.45);
    }
    .yaya-hero h2 { font-size: clamp(1.15rem,3vw,1.5rem); font-weight: 800; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
    .yaya-hero p { font-size: .85rem; color: #94A3B8; margin-top: 5px; }
    .badge-super {
        background: rgba(217,119,6,.22); border: 1px solid #D97706; color: #FBBF24;
        padding: 5px 13px; border-radius: 100px; font-size: .72rem; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase;
    }
    .unit-row {
        display: grid; gap: 14px; align-items: center;
        grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1fr) auto;
        padding: 14px 16px; border: 1px solid var(--border); border-radius: 13px;
        background: #fff; text-decoration: none; color: inherit;
        transition: border-color .18s, box-shadow .18s, transform .18s;
    }
    .unit-row:hover { border-color: var(--navy); box-shadow: var(--shadow); transform: translateY(-2px); }
    .unit-row + .unit-row { margin-top: 11px; }
    .unit-row-name { font-weight: 800; font-size: .92rem; color: var(--navy); display: flex; align-items: center; gap: 8px; }
    .unit-row-meta { font-size: .76rem; color: var(--gray); margin-top: 3px; }
    .track { height: 9px; background: #F1F5F9; border-radius: 100px; overflow: hidden; margin-top: 7px; }
    .track > span { display: block; height: 100%; border-radius: 100px; }
    .fill-mi  { background: linear-gradient(90deg,#10B981,#059669); }
    .fill-mts { background: linear-gradient(90deg,#3B82F6,#1D4ED8); }
    .fill-smk { background: linear-gradient(90deg,#F59E0B,#D97706); }
    .kondisi-row {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 12px 14px; border-radius: 12px; border: 1px solid transparent;
    }
    .kondisi-row + .kondisi-row { margin-top: 10px; }
    @media (max-width: 640px) {
        .unit-row { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')

{{-- ── HERO ── --}}
<div class="yaya-hero">
    <div>
        <h2><i data-lucide="landmark" style="width:24px;height:24px;"></i> Dashboard Admin Yayasan</h2>
        <p>
            Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror ·
            Konsolidasi {{ $perUnit->count() }} unit pendidikan (MI, MTS, SMK)
        </p>
    </div>
    <div style="display:flex;gap:9px;align-items:center;flex-wrap:wrap;">
        <span class="badge-super">Super Admin Access</span>
        <a href="{{ route('unit.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="building-2"></i> Rekap Unit</a>
    </div>
</div>

{{-- ── METRIK GLOBAL ── --}}
<div class="grid grid-stats" style="margin-bottom:22px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Nilai Aset</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#D97706;"><i data-lucide="banknote"></i></span>
        </div>
        <div class="stat-val" style="font-size:clamp(1.1rem,2.6vw,1.4rem);">{{ $stats['total_nilai'] }}</div>
        <div class="stat-sub">Estimasi seluruh inventaris yayasan</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Aset Konsolidasi</span>
            <span class="stat-ico" style="background:#DBEAFE;color:#2563EB;"><i data-lucide="package"></i></span>
        </div>
        <div class="stat-val">{{ number_format($stats['total_aset']) }} <small>unit</small></div>
        <div class="stat-sub">{{ $stats['total_jenis'] }} jenis barang · {{ $stats['total_ruangan'] }} ruangan</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Kondisi Baik Global</span>
            <span class="stat-ico" style="background:#D1FAE5;color:#059669;"><i data-lucide="check-circle"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ number_format($stats['aset_baik']) }} <small>unit</small></div>
        <div class="stat-sub">{{ $stats['persen_baik'] }}% siap digunakan</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Perlu Reparasi / Rusak</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="alert-triangle"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ number_format($stats['aset_rusak_ringan'] + $stats['aset_rusak_berat']) }} <small>unit</small></div>
        <div class="stat-sub">{{ $stats['aset_rusak_ringan'] }} rusak ringan · {{ $stats['aset_rusak_berat'] }} rusak berat</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Menunggu Verifikasi</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#B45309;"><i data-lucide="clock"></i></span>
        </div>
        <div class="stat-val" style="color:#B45309;">{{ $stats['laporan_pending'] }} <small>laporan</small></div>
        <div class="stat-sub">{{ $stats['laporan_proses'] }} sedang diproses</div>
    </div>

    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Laporan Terverifikasi</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="file-check-2"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ $stats['laporan_selesai'] }} <small>laporan</small></div>
        <div class="stat-sub">Arsip laporan yang sudah selesai</div>
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:22px;">
    {{-- ── DISTRIBUSI PER UNIT ── --}}
    <div class="card">
        <div class="card-head">
            <h3><i data-lucide="pie-chart"></i> Distribusi Inventaris per Unit Sekolah</h3>
            <span class="muted">Klik unit untuk melihat detail</span>
        </div>
        <div class="card-pad">
            @foreach($perUnit as $u)
                <a href="{{ $u['url'] }}" class="unit-row">
                    <div style="min-width:0;">
                        <div class="unit-row-name">
                            <i data-lucide="{{ $u['icon'] }}"></i> Unit {{ $u['kode'] }}
                        </div>
                        <div class="unit-row-meta">{{ $u['jenjang'] }} · {{ $u['ruangan'] }} ruangan · {{ $u['jenis_barang'] }} jenis barang</div>
                    </div>
                    <div>
                        <div style="font-size:.8rem;font-weight:800;color:var(--ink);">
                            {{ number_format($u['total_qty']) }} unit ({{ $u['persen'] }}%)
                        </div>
                        <div class="track">
                            <span class="fill-{{ strtolower($u['kode']) }}" style="width:{{ max(2, $u['persen']) }}%;"></span>
                        </div>
                        <div style="font-size:.72rem;color:var(--gray);margin-top:5px;">
                            Baik {{ number_format($u['baik']) }} · Ringan {{ $u['rusak_ringan'] }} · Berat {{ $u['rusak_berat'] }}
                        </div>
                    </div>
                    <span style="color:var(--navy);font-weight:800;font-size:.8rem;white-space:nowrap;">Detail →</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── STATUS KONDISI ── --}}
    <div class="card">
        <div class="card-head"><h3><i data-lucide="activity"></i> Status Kondisi Aset</h3></div>
        <div class="card-pad">
            <div class="kondisi-row" style="background:#F0FDF4;border-color:#BBF7D0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="color:#16A34A;"><i data-lucide="check-circle-2" style="width:21px;height:21px;"></i></span>
                    <div>
                        <div style="font-weight:800;font-size:.85rem;color:#166534;">Kondisi Baik</div>
                        <div style="font-size:.74rem;color:#15803D;">Siap operasional</div>
                    </div>
                </div>
                <div style="font-weight:800;font-size:1.05rem;color:#166534;">{{ number_format($stats['aset_baik']) }}</div>
            </div>

            <div class="kondisi-row" style="background:#FEFCE8;border-color:#FEF08A;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="color:#D97706;"><i data-lucide="alert-circle" style="width:21px;height:21px;"></i></span>
                    <div>
                        <div style="font-weight:800;font-size:.85rem;color:#854D0E;">Rusak Ringan</div>
                        <div style="font-size:.74rem;color:#A16207;">Butuh servis / perbaikan</div>
                    </div>
                </div>
                <div style="font-weight:800;font-size:1.05rem;color:#854D0E;">{{ number_format($stats['aset_rusak_ringan']) }}</div>
            </div>

            <div class="kondisi-row" style="background:#FEF2F2;border-color:#FECACA;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="color:#DC2626;"><i data-lucide="x-circle" style="width:21px;height:21px;"></i></span>
                    <div>
                        <div style="font-weight:800;font-size:.85rem;color:#991B1B;">Rusak Berat</div>
                        <div style="font-size:.74rem;color:#B91C1C;">Usulan penggantian / penghapusan</div>
                    </div>
                </div>
                <div style="font-weight:800;font-size:1.05rem;color:#991B1B;">{{ number_format($stats['aset_rusak_berat']) }}</div>
            </div>

            <div style="margin-top:16px;padding-top:14px;border-top:1px dashed var(--border);display:flex;gap:9px;flex-wrap:wrap;">
                <a href="{{ route('laporan.index', ['status' => 'pending']) }}" class="btn btn-red btn-sm">
                    <i data-lucide="file-search"></i> Verifikasi Laporan ({{ $stats['laporan_pending'] }})
                </a>
                <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">
                    <i data-lucide="users"></i> Kelola Akun
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── LAPORAN MENUNGGU VERIFIKASI ── --}}
<div class="card" style="margin-bottom:22px;">
    <div class="card-head">
        <h3><i data-lucide="file-bar-chart"></i> Laporan Menunggu Tindak Lanjut Yayasan</h3>
        <a href="{{ route('laporan.index') }}" class="muted" style="color:var(--blue);text-decoration:none;">Buka Laporan & Audit Global →</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Unit</th>
                    <th>Jenis</th>
                    <th>Judul Laporan</th>
                    <th>Pelapor</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($laporanTerbaru as $lap)
                    <tr>
                        <td class="td-nowrap" style="color:var(--gray);">{{ $lap->created_at?->format('d/m/Y H:i') }}</td>
                        <td><span class="badge badge-blue">{{ $lap->unit?->label() ?? '-' }}</span></td>
                        <td style="font-size:.8rem;">{{ $lap->jenisLabel() }}</td>
                        <td style="font-weight:700;">{{ $lap->judul }}</td>
                        <td style="font-size:.8rem;color:var(--gray);">{{ $lap->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            @if($lap->isProses())
                                <span class="badge badge-blue">Diproses</span>
                            @else
                                <span class="badge badge-amber">Menunggu</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i data-lucide="check-circle-2" style="width:34px;height:34px;stroke-width:1.4;"></i>
                                <strong>Tidak ada laporan yang menunggu</strong>
                                <p>Seluruh laporan unit sekolah telah ditindaklanjuti.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── LOG AKTIVITAS ── --}}
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="history"></i> Log Aktivitas Mutasi & Pelaporan Seluruh Yayasan</h3>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Unit</th>
                    <th>Keterangan Aktivitas</th>
                    <th>Petugas</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivities as $act)
                    <tr>
                        <td class="td-nowrap" style="color:var(--gray);">{{ $act['time'] }}</td>
                        <td><span class="badge badge-gray">{{ $act['unit'] }}</span></td>
                        <td style="font-weight:600;">
                            <i data-lucide="{{ $act['icon'] }}" style="width:13px;height:13px;color:var(--gray);"></i>
                            {{ $act['desc'] }}
                        </td>
                        <td style="font-size:.8rem;color:var(--gray);">{{ $act['user'] }}</td>
                        <td style="text-align:center;">
                            @php
                                $cls = match($act['status']) {
                                    'Masuk', 'Selesai' => 'badge-green',
                                    'Keluar'           => 'badge-amber',
                                    'Proses'           => 'badge-blue',
                                    default            => 'badge-gray',
                                };
                            @endphp
                            <span class="badge {{ $cls }}">{{ $act['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <strong>Belum ada aktivitas tercatat</strong>
                                <p>Mutasi barang & pelaporan dari unit sekolah akan tampil di sini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
