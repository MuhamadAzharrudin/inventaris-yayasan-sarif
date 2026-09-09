@extends('layouts.admin')

@section('page-title', 'Rekap Inventaris Semua Unit Sekolah')
@section('page-icon', 'building-2')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="building-2"></i> Rekap Inventaris Semua Unit Sekolah</h1>
        <p>Ringkasan jumlah data barang aset pada setiap unit di bawah naungan Yayasan Husnul Abror</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('dashboard.yayasan') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Dashboard</a>
        <a href="{{ route('laporan.index') }}" class="btn btn-blue"><i data-lucide="file-bar-chart"></i> Laporan & Audit</a>
    </div>
</div>

{{-- ── TOTAL YAYASAN ── --}}
<div class="grid grid-stats" style="margin-bottom:22px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Unit Barang</span>
            <span class="stat-ico" style="background:#DBEAFE;color:#2563EB;"><i data-lucide="package"></i></span>
        </div>
        <div class="stat-val">{{ number_format($total['total_qty']) }} <small>unit</small></div>
        <div class="stat-sub">Gabungan seluruh unit sekolah</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Jenis Barang</span>
            <span class="stat-ico" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="layers"></i></span>
        </div>
        <div class="stat-val">{{ number_format($total['jenis']) }} <small>jenis</small></div>
        <div class="stat-sub">Data barang terdaftar</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Ruangan Terdata</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="door-open"></i></span>
        </div>
        <div class="stat-val">{{ number_format($total['ruangan']) }} <small>ruang</small></div>
        <div class="stat-sub">Kelas, lab, aula & kantor</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Nilai Estimasi</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#D97706;"><i data-lucide="banknote"></i></span>
        </div>
        <div class="stat-val" style="font-size:clamp(1.05rem,2.4vw,1.3rem);">Rp {{ number_format($total['nilai'], 0, ',', '.') }}</div>
        <div class="stat-sub">Total estimasi aset yayasan</div>
    </div>
</div>

{{-- ── TABEL PER UNIT ── --}}
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="table-2"></i> Rincian Jumlah Data Barang per Unit</h3>
        <span class="muted">{{ $rows->count() }} unit pendidikan</span>
    </div>
    <div class="scroll-hint"><i data-lucide="move-horizontal" style="width:12px;height:12px;"></i> Geser tabel ke samping untuk melihat kolom lain</div>
    <div class="table-wrap">
        <table class="table table-wide">
            <thead>
                <tr>
                    <th>Unit Sekolah</th>
                    <th style="text-align:center;">Ruangan</th>
                    <th style="text-align:center;">Jenis Barang</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Baik</th>
                    <th style="text-align:center;">Rusak Ringan</th>
                    <th style="text-align:center;">Rusak Berat</th>
                    <th style="text-align:right;">Nilai Estimasi</th>
                    <th style="text-align:center;">Laporan Aktif</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>
                            <div style="font-weight:800;color:var(--navy);display:flex;align-items:center;gap:7px;">
                                <i data-lucide="{{ $row['unit']->icon() }}"></i> Unit {{ $row['unit']->label() }}
                            </div>
                            <div style="font-size:.75rem;color:var(--gray);">{{ $row['unit']->nama }} · {{ $row['unit']->jenjang() }}</div>
                        </td>
                        <td style="text-align:center;font-weight:700;">{{ $row['ruangan'] }}</td>
                        <td style="text-align:center;font-weight:700;">{{ number_format($row['jenis']) }}</td>
                        <td style="text-align:center;font-weight:800;color:var(--navy);">{{ number_format($row['total_qty']) }}</td>
                        <td style="text-align:center;"><span class="badge badge-green">{{ number_format($row['baik']) }}</span></td>
                        <td style="text-align:center;"><span class="badge badge-amber">{{ number_format($row['rusak_ringan']) }}</span></td>
                        <td style="text-align:center;"><span class="badge badge-red">{{ number_format($row['rusak_berat']) }}</span></td>
                        <td class="td-nowrap" style="text-align:right;font-weight:700;">Rp {{ number_format($row['nilai'], 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($row['laporan'] > 0)
                                <span class="badge badge-red">{{ $row['laporan'] }} laporan</span>
                            @else
                                <span class="badge badge-gray">Tidak ada</span>
                            @endif
                        </td>
                        <td>
                            <div class="cell-actions">
                                <a href="{{ route('unit.show', $row['unit']->id) }}" class="btn btn-primary btn-sm">
                                    <i data-lucide="eye" style="width:13px;height:13px;"></i> Detail
                                </a>
                                <a href="{{ route('unit.export', $row['unit']->id) }}" class="btn btn-ghost btn-sm">
                                    <i data-lucide="download" style="width:13px;height:13px;"></i> CSV
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total Yayasan</td>
                    <td style="text-align:center;">{{ $total['ruangan'] }}</td>
                    <td style="text-align:center;">{{ number_format($total['jenis']) }}</td>
                    <td style="text-align:center;">{{ number_format($total['total_qty']) }}</td>
                    <td colspan="3"></td>
                    <td class="td-nowrap" style="text-align:right;">Rp {{ number_format($total['nilai'], 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
