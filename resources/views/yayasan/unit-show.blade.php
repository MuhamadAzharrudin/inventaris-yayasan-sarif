@extends('layouts.admin')

@section('page-title', 'Detail Inventaris Unit ' . $unit->label())
@section('page-icon', $unit->icon())

@section('content')

<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('unit.index') }}" class="btn btn-ghost btn-icon" title="Kembali ke rekap unit">
                <i data-lucide="arrow-left"></i>
            </a>
            <i data-lucide="{{ $unit->icon() }}"></i> Detail Inventaris Unit {{ $unit->label() }}
        </h1>
        <p>
            {{ $unit->nama }} · {{ $unit->jenjang() }} · Kepala Unit: {{ $unit->kepala_unit }}
            · <span class="badge badge-purple">Tinjauan Yayasan (hanya lihat)</span>
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('unit.export', $unit->id) }}" class="btn btn-green"><i data-lucide="file-spreadsheet"></i> Export CSV</a>
        <a href="{{ route('laporan.index', ['unit' => $unit->id]) }}" class="btn btn-blue"><i data-lucide="file-search"></i> Laporan Unit Ini</a>
    </div>
</div>

{{-- ── REKAP UNIT ── --}}
<div class="grid grid-stats" style="margin-bottom:22px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Unit Barang</span>
            <span class="stat-ico" style="background:#DBEAFE;color:#2563EB;"><i data-lucide="package"></i></span>
        </div>
        <div class="stat-val">{{ number_format($rekap['total_qty']) }} <small>unit</small></div>
        <div class="stat-sub">{{ $rekap['jenis'] }} jenis barang · {{ $rekap['ruangan'] }} ruangan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Kondisi Baik</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="check-circle-2"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ number_format($rekap['baik']) }} <small>unit</small></div>
        <div class="stat-sub">
            {{ $rekap['total_qty'] > 0 ? round($rekap['baik'] / $rekap['total_qty'] * 100, 1) : 0 }}% dari total unit
        </div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Rusak Ringan</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#D97706;"><i data-lucide="wrench"></i></span>
        </div>
        <div class="stat-val" style="color:#B45309;">{{ number_format($rekap['rusak_ringan']) }} <small>unit</small></div>
        <div class="stat-sub">Butuh perbaikan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Rusak Berat</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="alert-octagon"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ number_format($rekap['rusak_berat']) }} <small>unit</small></div>
        <div class="stat-sub">Usulan penggantian</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Nilai Estimasi</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#B45309;"><i data-lucide="banknote"></i></span>
        </div>
        <div class="stat-val" style="font-size:clamp(1.05rem,2.4vw,1.3rem);">Rp {{ number_format($rekap['nilai'], 0, ',', '.') }}</div>
        <div class="stat-sub">Total aset unit ini</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Laporan Aktif</span>
            <span class="stat-ico" style="background:#EFF6FF;color:#2563EB;"><i data-lucide="file-search"></i></span>
        </div>
        <div class="stat-val">{{ $rekap['laporan_aktif'] }} <small>laporan</small></div>
        <div class="stat-sub">Menunggu / sedang diproses</div>
    </div>
</div>

{{-- ── REKAP PER RUANGAN ── --}}
<div class="card" style="margin-bottom:22px;">
    <div class="card-head">
        <h3><i data-lucide="door-open"></i> Rekap Aset per Ruangan</h3>
        <span class="muted">{{ $perRuangan->count() }} ruangan</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Ruangan</th>
                    <th>Tipe</th>
                    <th style="text-align:center;">Jenis</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Baik</th>
                    <th style="text-align:center;">Rusak</th>
                    <th style="text-align:right;">Nilai</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perRuangan as $r)
                    <tr>
                        <td style="font-weight:700;color:var(--navy);">{{ $r['lokasi']->nama }}</td>
                        <td><span class="badge badge-gray">{{ strtoupper($r['lokasi']->tipe) }}</span></td>
                        <td style="text-align:center;">{{ $r['jenis'] }}</td>
                        <td style="text-align:center;font-weight:800;">{{ number_format($r['total_qty']) }}</td>
                        <td style="text-align:center;color:#15803D;font-weight:700;">{{ number_format($r['baik']) }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-amber">{{ $r['rusak_ringan'] }}</span>
                            <span class="badge badge-red">{{ $r['rusak_berat'] }}</span>
                        </td>
                        <td class="td-nowrap" style="text-align:right;">Rp {{ number_format($r['nilai'], 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('unit.show', $unit->id) }}?lokasi={{ $r['lokasi']->id }}" class="btn btn-ghost btn-sm">
                                <i data-lucide="filter" style="width:13px;height:13px;"></i> Filter
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state"><strong>Unit ini belum memiliki ruangan</strong></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── DAFTAR SELURUH ASET UNIT ── --}}
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Daftar Seluruh Data Barang</h3>
        <span class="muted">{{ $assets->total() }} data barang</span>
    </div>

    <div class="card-pad" style="border-bottom:1px solid var(--border);">
        <form method="GET" action="{{ route('unit.show', $unit->id) }}" class="filter-bar">
            <div class="field">
                <label for="q">Cari barang</label>
                <input type="search" id="q" name="q" class="input" value="{{ $filters['q'] }}" placeholder="Nama / kode / spesifikasi…">
            </div>
            <div class="field">
                <label for="lokasi">Ruangan</label>
                <select id="lokasi" name="lokasi" class="select">
                    <option value="">Semua ruangan</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected($filters['lokasi'] === $loc->id)>{{ $loc->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="kondisi">Kondisi</label>
                <select id="kondisi" name="kondisi" class="select">
                    <option value="semua" @selected($filters['kondisi'] === 'semua')>Semua kondisi</option>
                    <option value="baik" @selected($filters['kondisi'] === 'baik')>Ada unit baik</option>
                    <option value="rusak_ringan" @selected($filters['kondisi'] === 'rusak_ringan')>Ada rusak ringan</option>
                    <option value="rusak_berat" @selected($filters['kondisi'] === 'rusak_berat')>Ada rusak berat</option>
                </select>
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary btn-block"><i data-lucide="search"></i> Terapkan</button>
                    <a href="{{ route('unit.show', $unit->id) }}" class="btn btn-soft">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="scroll-hint"><i data-lucide="move-horizontal" style="width:12px;height:12px;"></i> Geser tabel ke samping untuk melihat kolom lain</div>
    <div class="table-wrap">
        <table class="table table-wide">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori / Merek</th>
                    <th>Ruangan</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Kondisi</th>
                    <th style="text-align:right;">Nilai</th>
                    <th style="text-align:center;">QR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $i => $asset)
                    <tr>
                        <td style="color:var(--gray);font-weight:700;">{{ $assets->firstItem() + $i }}</td>
                        <td><span class="code">{{ $asset->kode_barang }}</span></td>
                        <td style="font-weight:700;">{{ $asset->nama_barang }}</td>
                        <td style="font-size:.8rem;color:var(--gray);">
                            {{ $asset->category->nama ?? 'Umum' }} / {{ $asset->merek->nama ?? 'Tanpa Merek' }}
                        </td>
                        <td style="font-size:.82rem;">{{ $asset->location->nama ?? '-' }}</td>
                        <td style="text-align:center;font-weight:800;">{{ number_format($asset->total_qty) }}</td>
                        <td style="text-align:center;" class="td-nowrap">
                            <span class="badge badge-green">{{ $asset->kondisi_baik }}</span>
                            <span class="badge badge-amber">{{ $asset->kondisi_rusak_ringan }}</span>
                            <span class="badge badge-red">{{ $asset->kondisi_rusak_berat }}</span>
                        </td>
                        <td class="td-nowrap" style="text-align:right;">Rp {{ number_format((float) $asset->nilai_estimasi, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            <img src="{{ $asset->qrUrl() }}" alt="QR {{ $asset->kode_barang }}" loading="lazy"
                                 style="width:44px;height:44px;border:1px solid #CBD5E1;border-radius:6px;background:#fff;padding:2px;">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i data-lucide="package-search" style="width:36px;height:36px;stroke-width:1.4;"></i>
                                <strong>Tidak ada data barang yang cocok</strong>
                                <p>Ubah kata kunci atau filter untuk melihat data lainnya.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assets->hasPages())
        <div class="card-pad pagination-wrap">{{ $assets->links() }}</div>
    @endif
</div>

@endsection
