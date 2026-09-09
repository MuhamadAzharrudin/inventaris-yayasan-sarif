@extends('layouts.admin')

@section('page-title', 'Label QR — ' . ($location->nama ?? 'Ruangan'))
@section('page-icon', 'qr-code')

@section('content')

<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('ruangan.show', ['ruangan' => $slug]) }}" class="btn btn-ghost btn-icon" title="Kembali ke daftar barang">
                <i data-lucide="arrow-left"></i>
            </a>
            <i data-lucide="qr-code"></i> Label QR — {{ $location->nama ?? 'Ruangan' }}
        </h1>
        <p>Label siap dicetak & ditempelkan pada fisik aset ruangan ini</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('aset.export-pdf', ['ruangan' => $slug]) }}" target="_blank" class="btn btn-red">
            <i data-lucide="printer"></i> Cetak Semua Label
        </a>
        @unless(auth()->user()->isSuperAdmin())
            <a href="{{ route('aset.cetak-label', ['ruangan' => $slug]) }}" class="btn btn-blue">
                <i data-lucide="check-square"></i> Pilih Barang Tertentu
            </a>
        @endunless
        <a href="{{ route('aset.export-excel', ['ruangan' => $slug]) }}" class="btn btn-ghost">
            <i data-lucide="file-spreadsheet"></i> Export CSV
        </a>
    </div>
</div>

<div class="grid grid-cards">
    @forelse($assets as $asset)
        <div class="card" style="padding:15px;border-style:dashed;border-color:#CBD5E1;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;border-bottom:1px solid var(--border);padding-bottom:9px;margin-bottom:11px;">
                <span style="font-size:.66rem;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:.04em;">
                    Inventaris Husnul Abror
                </span>
                <span class="badge badge-blue">{{ $location->nama }}</span>
            </div>

            <div style="display:flex;gap:13px;align-items:center;">
                <img src="{{ $asset->qrUrl() }}" alt="QR {{ $asset->kode_barang }}" loading="lazy"
                     style="width:84px;height:84px;background:#fff;padding:4px;border:1px solid var(--border);border-radius:9px;flex-shrink:0;">
                <div style="min-width:0;">
                    <div style="font-weight:800;font-size:.92rem;">{{ $asset->nama_barang }}</div>
                    <div style="font-size:.76rem;color:var(--gray);margin:3px 0;">
                        {{ $asset->merek->nama ?? 'Tanpa Merek' }} · {{ $asset->category->nama ?? 'Umum' }}
                    </div>
                    <span class="code">{{ $asset->kode_barang }}</span>
                </div>
            </div>

            <div style="margin-top:11px;padding-top:9px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:.72rem;color:var(--gray);">
                <span>Total: {{ $asset->total_qty }} unit</span>
                <span>Scan untuk detail & pelaporan</span>
            </div>
        </div>
    @empty
        <div class="card card-pad" style="grid-column:1/-1;">
            <div class="empty-state">
                <i data-lucide="qr-code" style="width:38px;height:38px;stroke-width:1.4;"></i>
                <strong>Belum ada barang untuk dicetak labelnya</strong>
                <p>Tambahkan barang pada ruangan ini terlebih dahulu.</p>
            </div>
        </div>
    @endforelse
</div>

@endsection
