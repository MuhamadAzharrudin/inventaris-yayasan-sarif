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

{{-- ── BULK ACTION BAR ── --}}
<form id="bulkDeleteForm" action="{{ route('mutasi.bulkDestroy') }}" method="POST" onsubmit="return confirmBulkDelete(event);">
    @csrf
    <div id="bulkActionBar" style="display:none;background:#FEF2F2;border:1px solid #FECACA;border-radius:13px;padding:12px 18px;margin-bottom:16px;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;box-shadow:0 4px 12px rgba(220,38,38,.08);">
        <div style="font-size:.88rem;color:#991B1B;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i data-lucide="check-square" style="width:16px;height:16px;"></i>
            <span id="bulkSelectedText">0 data dipilih</span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" class="btn btn-soft btn-sm" onclick="clearAllSelections()">Batal Pilihan</button>
            <button type="submit" class="btn btn-red btn-sm">
                <i data-lucide="trash-2"></i> Hapus Terpilih (<span id="bulkCountBadge">0</span>)
            </button>
        </div>
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
                    <th style="width:38px;text-align:center;">
                        <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" style="cursor:pointer;width:16px;height:16px;accent-color:#DC2626;" title="Pilih semua di halaman ini">
                    </th>
                    <th style="width:44px;">No</th>
                    <th>Waktu Masuk</th>
                    @if($isYayasan)<th>Unit</th>@endif
                    <th>Barang</th>
                    <th>Kategori & Merek</th>
                    <th>Lokasi Ruangan</th>
                    <th>Jenis</th>
                    <th style="text-align:center;">Qty</th>
                    <th>Keterangan</th>
                    <th style="text-align:center;width:60px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $i => $m)
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="ids[]" value="{{ $m->id }}" class="check-item" onchange="updateBulkState()" style="cursor:pointer;width:16px;height:16px;accent-color:#DC2626;">
                        </td>
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
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" title="Hapus catatan" style="color:#DC2626;"
                                    onclick="deleteSingleItem({{ $m->id }})">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isYayasan ? 11 : 10 }}">
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
</form>

{{-- Form hapus satuan tersembunyi --}}
<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

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
<script>
    function getCheckedBoxes() {
        return Array.from(document.querySelectorAll('.check-item:checked'));
    }

    function updateBulkState() {
        const checked = getCheckedBoxes();
        const count = checked.length;
        const bar = document.getElementById('bulkActionBar');
        const badge = document.getElementById('bulkCountBadge');
        const text = document.getElementById('bulkSelectedText');
        const selectAll = document.getElementById('selectAllCheckbox');
        const allItems = document.querySelectorAll('.check-item');

        if (count > 0) {
            bar.style.display = 'flex';
            badge.textContent = count;
            text.textContent = count + ' data mutasi dipilih';
        } else {
            bar.style.display = 'none';
        }

        if (selectAll && allItems.length > 0) {
            selectAll.checked = (count === allItems.length);
            selectAll.indeterminate = (count > 0 && count < allItems.length);
        }
    }

    function toggleSelectAll(master) {
        const items = document.querySelectorAll('.check-item');
        items.forEach(item => { item.checked = master.checked; });
        updateBulkState();
    }

    function clearAllSelections() {
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) selectAll.checked = false;
        const items = document.querySelectorAll('.check-item');
        items.forEach(item => { item.checked = false; });
        updateBulkState();
    }

    function confirmBulkDelete(e) {
        const count = getCheckedBoxes().length;
        if (count === 0) {
            e.preventDefault();
            alert('Pilih setidaknya satu data yang ingin dihapus.');
            return false;
        }
        if (!confirm('Apakah Anda yakin ingin menghapus ' + count + ' data mutasi barang masuk yang dipilih?')) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    function deleteSingleItem(id) {
        if (confirm('Apakah Anda yakin ingin menghapus catatan barang masuk ini?')) {
            const form = document.getElementById('singleDeleteForm');
            form.action = '{{ url("/mutasi") }}/' + id;
            form.submit();
        }
    }

    @unless($isYayasan)
        @if($errors->any() && old('asset_id'))
            document.addEventListener('DOMContentLoaded', () => openModal('masukModal'));
        @endif
    @endunless
</script>
@endsection
