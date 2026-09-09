@extends('layouts.admin')

@php
    $isYayasan = auth()->user()->isSuperAdmin();
    $today     = now()->toDateString();
@endphp

@section('page-title', $isYayasan ? 'Audit Barang Masuk' : 'Barang Masuk')
@section('page-icon', 'arrow-down-left-square')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="arrow-down-left-square" style="color:#16A34A;"></i>
            {{ $isYayasan ? 'Audit Barang Masuk Seluruh Unit' : 'Barang Masuk' }}
        </h1>
        <p>Riwayat pengadaan barang baru, barang pengganti, dan pengembalian barang pinjaman</p>
    </div>
    @unless($isYayasan)
    <div class="page-actions">
        <button type="button" class="btn btn-green" onclick="openModal('masukModal')">
            <i data-lucide="plus-circle"></i> Catat Barang Masuk
        </button>
    </div>
    @endunless
</div>

{{-- ── RINGKASAN ── --}}
<div class="grid grid-stats" style="margin-bottom:20px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Transaksi</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="arrow-down-left-square"></i></span>
        </div>
        <div class="stat-val">{{ number_format($ringkasan['transaksi']) }}</div>
        <div class="stat-sub">Catatan barang masuk</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Unit Masuk</span>
            <span class="stat-ico" style="background:#DBEAFE;color:#2563EB;"><i data-lucide="package-plus"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ number_format($ringkasan['unit']) }} <small>unit</small></div>
        <div class="stat-sub">Akumulasi seluruh periode</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Transaksi Bulan Ini</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#D97706;"><i data-lucide="calendar-days"></i></span>
        </div>
        <div class="stat-val">{{ number_format($ringkasan['bulan_ini']) }}</div>
        <div class="stat-sub">{{ now()->translatedFormat('F Y') }}</div>
    </div>
</div>

{{-- ── FILTER ── --}}
<div class="card card-pad" style="margin-bottom:18px;">
    <form method="GET" action="{{ route('laporan.masuk') }}" class="filter-bar">
        <div class="field">
            <label for="q">Kata kunci</label>
            <input type="search" id="q" name="q" class="input" value="{{ $filters['q'] }}" placeholder="Barang / keterangan…">
        </div>
        @if($isYayasan)
        <div class="field">
            <label for="unit">Unit sekolah</label>
            <select id="unit" name="unit" class="select">
                <option value="">Semua unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected($filters['unit'] === $unit->id)>{{ $unit->nama }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="field">
            <label for="jenis">Jenis</label>
            <select id="jenis" name="jenis" class="select">
                <option value="semua" @selected($filters['jenis'] === 'semua')>Semua jenis</option>
                <option value="pengadaan" @selected($filters['jenis'] === 'pengadaan')>Pengadaan barang baru</option>
                <option value="penggantian_baru" @selected($filters['jenis'] === 'penggantian_baru')>Barang pengganti</option>
                <option value="pengembalian" @selected($filters['jenis'] === 'pengembalian')>Pengembalian pinjaman</option>
            </select>
        </div>
        <div class="field">
            <label for="dari">Dari tanggal</label>
            <input type="date" id="dari" name="dari" class="input" value="{{ $filters['dari'] }}">
        </div>
        <div class="field">
            <label for="sampai">Sampai tanggal</label>
            <input type="date" id="sampai" name="sampai" class="input" value="{{ $filters['sampai'] }}">
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-block"><i data-lucide="filter"></i> Terapkan</button>
                <a href="{{ route('laporan.masuk') }}" class="btn btn-soft">Reset</a>
            </div>
        </div>
    </form>
</div>

{{-- ── TABEL ── --}}
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Log Mutasi Barang Masuk</h3>
        <span class="muted">{{ $mutations->total() }} catatan</span>
    </div>
    <div class="scroll-hint"><i data-lucide="move-horizontal" style="width:12px;height:12px;"></i> Geser tabel ke samping untuk melihat kolom lain</div>
    <div class="table-wrap">
        <table class="table table-wide">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Waktu Masuk</th>
                    @if($isYayasan)<th>Unit</th>@endif
                    <th>Barang</th>
                    <th>Kategori & Merek</th>
                    <th>Lokasi Ruangan</th>
                    <th>Jenis</th>
                    <th style="text-align:center;">Qty</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $i => $m)
                    <tr>
                        <td style="color:var(--gray);font-weight:700;">{{ $mutations->firstItem() + $i }}</td>
                        <td class="td-nowrap" style="color:var(--gray);">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                        @if($isYayasan)
                            <td><span class="badge badge-blue">{{ $m->unit?->label() ?? '-' }}</span></td>
                        @endif
                        <td>
                            <div style="font-weight:700;">{{ $m->asset->nama_barang ?? 'Barang dihapus' }}</div>
                            <div style="font-size:.74rem;"><span class="code">{{ $m->asset->kode_barang ?? '-' }}</span></div>
                        </td>
                        <td style="font-size:.8rem;color:var(--gray);">
                            {{ $m->asset->category->nama ?? '-' }} / {{ $m->asset->merek->nama ?? '-' }}
                        </td>
                        <td style="font-size:.82rem;font-weight:600;">{{ $m->location->nama ?? '-' }}</td>
                        <td>
                            @if($m->jenis === 'pengembalian')
                                <span class="badge badge-purple"><i data-lucide="undo-2" style="width:11px;height:11px;"></i> Pengembalian</span>
                            @elseif($m->jenis === 'penggantian_baru')
                                <span class="badge badge-blue"><i data-lucide="package-plus" style="width:11px;height:11px;"></i> Pengganti</span>
                            @else
                                <span class="badge badge-green"><i data-lucide="shopping-cart" style="width:11px;height:11px;"></i> Pengadaan</span>
                            @endif
                        </td>
                        <td style="text-align:center;"><span class="badge badge-green">+{{ $m->qty }} unit</span></td>
                        <td style="font-size:.8rem;color:var(--gray);max-width:300px;">{{ $m->keterangan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isYayasan ? 9 : 8 }}">
                            <div class="empty-state">
                                <i data-lucide="package-search" style="width:38px;height:38px;stroke-width:1.4;"></i>
                                <strong>Belum ada log barang masuk</strong>
                                <p>Pendataan barang baru dan pengembalian pinjaman akan tercatat di sini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="card-pad pagination-wrap">{{ $mutations->links() }}</div>
    @endif
</div>

@unless($isYayasan)
{{-- ══ MODAL CATAT BARANG MASUK ══ --}}
<div class="modal" id="masukModal">
    <div class="modal-box">
        <form action="{{ route('mutasi.masuk.store') }}" method="POST">
            @csrf
            <div class="modal-head">
                <h3>Catat Barang Masuk</h3>
                <button type="button" class="modal-close" onclick="closeModal('masukModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info" style="margin:0;">
                    <i data-lucide="info"></i>
                    <div>Unit yang masuk dicatat sebagai <strong>kondisi baik</strong> sehingga total unit barang
                        bertambah sesuai jumlah yang diisi.</div>
                </div>

                <div class="field">
                    <label for="assetMasuk">Barang <span class="req">*</span></label>
                    <select name="asset_id" id="assetMasuk" class="select" required>
                        <option value="">— Pilih barang —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" @selected((string) old('asset_id') === (string) $asset->id)>
                                [{{ $asset->kode_barang }}] {{ $asset->nama_barang }} — {{ $asset->location->nama ?? 'Ruangan' }}
                                (stok {{ $asset->total_qty }} unit)
                            </option>
                        @endforeach
                    </select>
                    <span class="hint">Barang baru yang belum pernah didata ditambahkan lewat menu ruangan → Tambah Barang.</span>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="jenisMasuk">Jenis barang masuk <span class="req">*</span></label>
                        <select name="jenis" id="jenisMasuk" class="select" required>
                            <option value="pengadaan" @selected(old('jenis') === 'pengadaan')>Pengadaan barang baru</option>
                            <option value="penggantian_baru" @selected(old('jenis') === 'penggantian_baru')>Barang pengganti diterima</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="qtyMasuk">Jumlah unit <span class="req">*</span></label>
                        <input type="number" id="qtyMasuk" name="qty" class="input" min="1" max="10000" required value="{{ old('qty', 1) }}">
                    </div>
                </div>

                <div class="field">
                    <label for="tanggalMasuk">Tanggal masuk <span class="req">*</span></label>
                    <input type="date" id="tanggalMasuk" name="tanggal_masuk" class="input" required
                           max="{{ $today }}" value="{{ old('tanggal_masuk', $today) }}">
                </div>

                <div class="field">
                    <label for="keteranganMasuk">Keterangan</label>
                    <textarea id="keteranganMasuk" name="keterangan" class="textarea" rows="2" maxlength="500"
                              placeholder="Contoh: pengadaan APBS semester ganjil / pengganti unit rusak berat">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('masukModal')">Batal</button>
                <button type="submit" class="btn btn-green"><i data-lucide="save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endunless

@endsection

@section('scripts')
@unless($isYayasan)
@if($errors->any() && old('asset_id'))
<script>document.addEventListener('DOMContentLoaded', () => openModal('masukModal'));</script>
@endif
@endunless
@endsection
