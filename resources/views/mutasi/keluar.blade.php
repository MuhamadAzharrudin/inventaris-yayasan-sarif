@extends('layouts.admin')

@php
    $isYayasan = auth()->user()->isSuperAdmin();
    $today     = now()->toDateString();
@endphp

@section('page-title', $isYayasan ? 'Audit Barang Keluar' : 'Barang Keluar')
@section('page-icon', 'arrow-up-right-square')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="arrow-up-right-square" style="color:#EA580C;"></i>
            {{ $isYayasan ? 'Audit Barang Keluar Seluruh Unit' : 'Barang Keluar' }}
        </h1>
        <p>
            @if($isYayasan)
                Riwayat barang rusak yang diajukan penggantian & barang yang dipinjamkan oleh unit sekolah
            @else
                Buat laporan barang rusak yang harus diganti dan barang yang dipinjamkan keluar ruangan
            @endif
        </p>
    </div>
    @unless($isYayasan)
    <div class="page-actions">
        <button type="button" class="btn btn-red" onclick="openKeluarModal('penggantian')">
            <i data-lucide="hammer"></i> Laporan Barang Rusak
        </button>
        <button type="button" class="btn btn-amber" onclick="openKeluarModal('peminjaman')">
            <i data-lucide="handshake"></i> Laporan Peminjaman
        </button>
    </div>
    @endunless
</div>

{{-- ── RINGKASAN ── --}}
<div class="grid grid-stats" style="margin-bottom:20px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Transaksi</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#EA580C;"><i data-lucide="arrow-up-right-square"></i></span>
        </div>
        <div class="stat-val">{{ number_format($ringkasan['transaksi']) }}</div>
        <div class="stat-sub">Catatan barang keluar</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Diajukan Penggantian</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="hammer"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ number_format($ringkasan['penggantian']) }} <small>unit</small></div>
        <div class="stat-sub">Barang rusak keluar dari ruangan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Sedang Dipinjam</span>
            <span class="stat-ico" style="background:#F3E8FF;color:#7C3AED;"><i data-lucide="handshake"></i></span>
        </div>
        <div class="stat-val" style="color:#6B21A8;">{{ number_format($ringkasan['dipinjam']) }} <small>unit</small></div>
        <div class="stat-sub">Belum dikembalikan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Pinjaman Terlambat</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#B91C1C;"><i data-lucide="alarm-clock"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ number_format($ringkasan['terlambat']) }}</div>
        <div class="stat-sub">Melewati rencana kembali</div>
    </div>
</div>

{{-- ── FILTER ── --}}
<div class="card card-pad" style="margin-bottom:18px;">
    <form method="GET" action="{{ route('laporan.keluar') }}" class="filter-bar">
        <div class="field">
            <label for="q">Kata kunci</label>
            <input type="search" id="q" name="q" class="input" value="{{ $filters['q'] }}" placeholder="Barang / peminjam / keterangan…">
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
                <option value="penggantian" @selected($filters['jenis'] === 'penggantian')>Penggantian barang rusak</option>
                <option value="peminjaman" @selected($filters['jenis'] === 'peminjaman')>Peminjaman barang</option>
                <option value="penghapusan" @selected($filters['jenis'] === 'penghapusan')>Penghapusan barang</option>
            </select>
        </div>
        <div class="field">
            <label for="status_pinjam">Status pinjaman</label>
            <select id="status_pinjam" name="status_pinjam" class="select">
                <option value="semua" @selected($filters['status_pinjam'] === 'semua')>Semua status</option>
                <option value="dipinjam" @selected($filters['status_pinjam'] === 'dipinjam')>Masih dipinjam</option>
                <option value="dikembalikan" @selected($filters['status_pinjam'] === 'dikembalikan')>Sudah dikembalikan</option>
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
                <a href="{{ route('laporan.keluar') }}" class="btn btn-soft">Reset</a>
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
        <h3><i data-lucide="list"></i> Riwayat Barang Keluar</h3>
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
                    <th>Tanggal Keluar</th>
                    @if($isYayasan)<th>Unit</th>@endif
                    <th>Barang</th>
                    <th>Jenis & Kondisi</th>
                    <th style="text-align:center;">Qty</th>
                    <th>Peminjam / Keterangan</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $i => $m)
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="ids[]" value="{{ $m->id }}" class="check-item" onchange="updateBulkState()" style="cursor:pointer;width:16px;height:16px;accent-color:#DC2626;">
                        </td>
                        <td style="color:var(--gray);font-weight:700;">{{ $mutations->firstItem() + $i }}</td>
                        <td class="td-nowrap" style="color:var(--gray);">
                            {{ optional($m->tanggal_keluar)->format('d/m/Y') ?? $m->created_at?->format('d/m/Y') }}
                            <div style="font-size:.72rem;">{{ $m->created_at?->format('H:i') }}</div>
                        </td>
                        @if($isYayasan)
                            <td><span class="badge badge-blue">{{ $m->unit?->label() ?? '-' }}</span></td>
                        @endif
                        <td>
                            <div style="font-weight:700;">{{ $m->asset->nama_barang ?? 'Barang dihapus' }}</div>
                            <div style="font-size:.74rem;color:var(--gray);">
                                <span class="code">{{ $m->asset->kode_barang ?? '-' }}</span>
                                {{ $m->location->nama ?? '' }}
                            </div>
                        </td>
                        <td>
                            @if($m->jenis === 'peminjaman')
                                <span class="badge badge-purple"><i data-lucide="handshake" style="width:11px;height:11px;"></i> Peminjaman</span>
                            @elseif($m->jenis === 'penggantian')
                                <span class="badge badge-red"><i data-lucide="hammer" style="width:11px;height:11px;"></i> Penggantian</span>
                            @else
                                <span class="badge badge-gray">{{ $m->jenisLabel() }}</span>
                            @endif
                            <div style="font-size:.73rem;color:var(--gray);margin-top:3px;">{{ $m->kondisiSumberLabel() }}</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-amber">-{{ $m->qty }} unit</span>
                        </td>
                        <td style="font-size:.8rem;max-width:280px;">
                            @if($m->peminjam)
                                <div style="font-weight:700;">{{ $m->peminjam }}</div>
                                @if($m->kontak_peminjam)
                                    <div style="font-size:.73rem;color:var(--gray);">{{ $m->kontak_peminjam }}</div>
                                @endif
                            @endif
                            <div style="color:var(--gray);">{{ $m->keterangan }}</div>
                            @if($m->tanggal_kembali_rencana)
                                <div style="font-size:.73rem;color:var(--gray);margin-top:3px;">
                                    <i data-lucide="calendar-clock" style="width:11px;height:11px;"></i>
                                    Rencana kembali: {{ $m->tanggal_kembali_rencana->format('d/m/Y') }}
                                    @if($m->tanggal_kembali_aktual)
                                        · Kembali: {{ $m->tanggal_kembali_aktual->format('d/m/Y') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($m->jenis === 'peminjaman')
                                @if($m->status_pinjam === 'dikembalikan')
                                    <span class="badge badge-green">Dikembalikan</span>
                                @elseif($m->isTerlambat())
                                    <span class="badge badge-red">Terlambat</span>
                                @else
                                    <span class="badge badge-amber">Dipinjam</span>
                                @endif
                            @else
                                @if($m->report)
                                    @if($m->report->isSelesai())
                                        <span class="badge {{ $m->report->isRejected() ? 'badge-red' : 'badge-green' }}">
                                            {{ $m->report->isRejected() ? 'Ditolak' : 'Disetujui' }}
                                        </span>
                                    @elseif($m->report->isProses())
                                        <span class="badge badge-blue">Diproses</span>
                                    @else
                                        <span class="badge badge-amber">Menunggu</span>
                                    @endif
                                @else
                                    <span class="badge badge-gray">Tercatat</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            <div class="cell-actions">
                                @if($m->report)
                                    <a href="{{ route('laporan.pdf', $m->report->id) }}" target="_blank" class="btn btn-ghost btn-sm" title="Cetak laporan">
                                        <i data-lucide="printer" style="width:13px;height:13px;"></i>
                                    </a>
                                @endif
                                @if(! $isYayasan && $m->isPinjamAktif())
                                    <button type="button" class="btn btn-green btn-sm"
                                        onclick="openKembaliModal({{ $m->id }}, @js($m->asset->nama_barang ?? 'Barang'), {{ $m->qty }}, @js(optional($m->tanggal_keluar)->format('Y-m-d')))">
                                        <i data-lucide="undo-2" style="width:13px;height:13px;"></i> Kembalikan
                                    </button>
                                @endif
                                <button type="button" class="btn btn-ghost btn-icon btn-sm" title="Hapus catatan" style="color:#DC2626;"
                                        onclick="deleteSingleItem({{ $m->id }})">
                                    <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isYayasan ? 10 : 9 }}">
                            <div class="empty-state">
                                <i data-lucide="package-x" style="width:38px;height:38px;stroke-width:1.4;"></i>
                                <strong>Belum ada catatan barang keluar</strong>
                                <p>
                                    @unless($isYayasan)
                                        Gunakan tombol "Laporan Barang Rusak" atau "Laporan Peminjaman" di atas untuk mencatat barang keluar.
                                    @else
                                        Unit sekolah belum mencatat barang keluar pada rentang filter ini.
                                    @endunless
                                </p>
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
{{-- ══ MODAL: BUAT LAPORAN BARANG KELUAR ══ --}}
<div class="modal" id="keluarModal">
    <div class="modal-box" style="max-width:600px;">
        <form action="{{ route('mutasi.keluar.store') }}" method="POST">
            @csrf
            <div class="modal-head">
                <h3 id="keluarModalTitle">Laporan Barang Keluar</h3>
                <button type="button" class="modal-close" onclick="closeModal('keluarModal')">✕</button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="jenis" id="jenisKeluar" value="penggantian">

                <div class="alert alert-info" style="margin:0;" id="keluarInfo">
                    <i data-lucide="info"></i>
                    <div id="keluarInfoText"></div>
                </div>

                <div class="field">
                    <label for="assetKeluar">Barang <span class="req">*</span></label>
                    <select name="asset_id" id="assetKeluar" class="select" required onchange="updateKeluarStok()">
                        <option value="">— Pilih barang —</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}"
                                data-nama="{{ $asset->nama_barang }}"
                                data-kode="{{ $asset->kode_barang }}"
                                data-lokasi="{{ $asset->location->nama ?? '-' }}"
                                data-baik="{{ $asset->kondisi_baik }}"
                                data-ringan="{{ $asset->kondisi_rusak_ringan }}"
                                data-berat="{{ $asset->kondisi_rusak_berat }}"
                                data-dipinjam="{{ $asset->qty_dipinjam }}"
                                data-siap="{{ $asset->qty_siap_pinjam }}"
                                @selected((string) old('asset_id') === (string) $asset->id)>
                                [{{ $asset->kode_barang }}] {{ $asset->nama_barang }} — {{ $asset->location->nama ?? 'Ruangan' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="hint" id="stokHint">Pilih barang untuk melihat ketersediaan unitnya.</span>
                </div>

                {{-- Kondisi sumber: hanya untuk penggantian --}}
                <div class="field" id="fieldKondisi">
                    <label for="kondisiSumber">Kondisi unit yang dikeluarkan <span class="req">*</span></label>
                    <select name="kondisi_sumber" id="kondisiSumber" class="select" onchange="updateKeluarStok()">
                        <option value="rusak_ringan" @selected(old('kondisi_sumber') === 'rusak_ringan')>Rusak Ringan</option>
                        <option value="rusak_berat" @selected(old('kondisi_sumber', 'rusak_berat') === 'rusak_berat')>Rusak Berat</option>
                    </select>
                    <span class="hint">Hanya unit rusak yang dapat diajukan penggantian.</span>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="qtyKeluar">Jumlah unit <span class="req">*</span></label>
                        <input type="number" id="qtyKeluar" name="qty" class="input" min="1" required value="{{ old('qty', 1) }}">
                        <span class="hint" id="qtyHint"></span>
                    </div>
                    <div class="field">
                        <label for="tanggalKeluar">Tanggal keluar <span class="req">*</span></label>
                        <input type="date" id="tanggalKeluar" name="tanggal_keluar" class="input" required
                               max="{{ $today }}" value="{{ old('tanggal_keluar', $today) }}">
                    </div>
                </div>

                {{-- Data peminjaman --}}
                <div id="fieldPinjam" style="display:none;flex-direction:column;gap:14px;">
                    <div class="form-grid">
                        <div class="field">
                            <label for="peminjam">Nama peminjam <span class="req">*</span></label>
                            <input type="text" id="peminjam" name="peminjam" class="input" maxlength="150"
                                   value="{{ old('peminjam') }}" placeholder="Contoh: Panitia Kegiatan Pramuka">
                        </div>
                        <div class="field">
                            <label for="kontakPeminjam">Kontak peminjam</label>
                            <input type="text" id="kontakPeminjam" name="kontak_peminjam" class="input" maxlength="60"
                                   value="{{ old('kontak_peminjam') }}" placeholder="No. HP / WhatsApp">
                        </div>
                    </div>
                    <div class="field">
                        <label for="tanggalKembaliRencana">Rencana tanggal kembali <span class="req">*</span></label>
                        <input type="date" id="tanggalKembaliRencana" name="tanggal_kembali_rencana" class="input"
                               value="{{ old('tanggal_kembali_rencana', now()->addDays(3)->toDateString()) }}">
                        <span class="hint">Tidak boleh lebih awal dari tanggal keluar.</span>
                    </div>
                </div>

                <div class="field">
                    <label for="keteranganKeluar" id="labelKeterangan">Alasan / keperluan <span class="req">*</span></label>
                    <textarea id="keteranganKeluar" name="keterangan" class="textarea" rows="3" required maxlength="1000"
                              placeholder="Jelaskan alasan barang dikeluarkan…">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('keluarModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitKeluar">
                    <i data-lucide="send"></i> Simpan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: PENGEMBALIAN BARANG ══ --}}
<div class="modal" id="kembaliModal">
    <div class="modal-box">
        <form id="kembaliForm" method="POST" action="">
            @csrf
            <div class="modal-head">
                <h3>Catat Pengembalian Barang</h3>
                <button type="button" class="modal-close" onclick="closeModal('kembaliModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info" style="margin:0;">
                    <i data-lucide="info"></i>
                    <div id="kembaliInfo"></div>
                </div>

                <div class="field">
                    <label for="tanggalKembali">Tanggal pengembalian <span class="req">*</span></label>
                    <input type="date" id="tanggalKembali" name="tanggal_kembali" class="input" required max="{{ $today }}" value="{{ $today }}">
                    <span class="hint" id="kembaliHint"></span>
                </div>

                <div class="field">
                    <label for="kondisiKembali">Kondisi barang saat kembali <span class="req">*</span></label>
                    <select id="kondisiKembali" name="kondisi_kembali" class="select" required>
                        <option value="baik">Baik — kondisi stok tidak berubah</option>
                        <option value="rusak_ringan">Rusak ringan — unit dipindah ke rusak ringan</option>
                        <option value="rusak_berat">Rusak berat — unit dipindah ke rusak berat</option>
                    </select>
                </div>

                <div class="field">
                    <label for="catatanKembali">Catatan pengembalian</label>
                    <textarea id="catatanKembali" name="catatan_kembali" class="textarea" rows="2" maxlength="500"
                              placeholder="Contoh: lensa proyektor tergores tipis"></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('kembaliModal')">Batal</button>
                <button type="submit" class="btn btn-green"><i data-lucide="undo-2"></i> Simpan Pengembalian</button>
            </div>
        </form>
    </div>
</div>
@endunless

@endsection

@section('scripts')
@unless($isYayasan)
<script>
    const KEMBALI_URL_BASE = @json(url('barang-keluar'));

    function openKeluarModal(jenis) {
        document.getElementById('jenisKeluar').value = jenis;

        const isPinjam = jenis === 'peminjaman';
        document.getElementById('keluarModalTitle').textContent = isPinjam
            ? 'Laporan Peminjaman Barang'
            : 'Laporan Barang Rusak untuk Diganti';
        document.getElementById('keluarInfoText').innerHTML = isPinjam
            ? 'Unit yang dipinjamkan diambil dari <strong>kondisi baik</strong> yang belum dipinjam. '
              + 'Stok ruangan tidak berkurang, namun unit tersebut tercatat sedang keluar sampai dikembalikan.'
            : 'Unit rusak yang dikeluarkan akan <strong>mengurangi stok kondisi rusak</strong> dan total unit barang, '
              + 'lalu laporan penggantian dikirim ke Admin Yayasan untuk diverifikasi.';

        document.getElementById('fieldKondisi').style.display = isPinjam ? 'none' : 'flex';
        document.getElementById('fieldPinjam').style.display  = isPinjam ? 'flex' : 'none';
        document.getElementById('labelKeterangan').innerHTML  = isPinjam
            ? 'Keperluan peminjaman <span class="req">*</span>'
            : 'Alasan penggantian <span class="req">*</span>';

        const peminjam = document.getElementById('peminjam');
        const rencana  = document.getElementById('tanggalKembaliRencana');
        peminjam.required = isPinjam;
        rencana.required  = isPinjam;
        document.getElementById('kondisiSumber').required = !isPinjam;

        document.getElementById('btnSubmitKeluar').className = isPinjam ? 'btn btn-amber' : 'btn btn-red';

        updateKeluarStok();
        openModal('keluarModal');
    }

    function updateKeluarStok() {
        const select   = document.getElementById('assetKeluar');
        const selected = select.options[select.selectedIndex];
        const jenis    = document.getElementById('jenisKeluar').value;
        const qty      = document.getElementById('qtyKeluar');
        const stokHint = document.getElementById('stokHint');
        const qtyHint  = document.getElementById('qtyHint');

        if (!selected || !selected.value) {
            stokHint.textContent = 'Pilih barang untuk melihat ketersediaan unitnya.';
            qtyHint.textContent  = '';
            qty.max = '';
            return;
        }

        const num = (k) => parseInt(selected.getAttribute('data-' + k) || '0', 10);
        const baik = num('baik'), ringan = num('ringan'), berat = num('berat');
        const dipinjam = num('dipinjam'), siap = num('siap');

        stokHint.textContent = `Stok ${selected.getAttribute('data-nama')}: `
            + `baik ${baik}, rusak ringan ${ringan}, rusak berat ${berat}`
            + (dipinjam > 0 ? `, sedang dipinjam ${dipinjam}` : '');

        let maksimal;
        if (jenis === 'peminjaman') {
            maksimal = siap;
            qtyHint.textContent = maksimal > 0
                ? `Maksimal ${maksimal} unit kondisi baik yang siap dipinjamkan.`
                : 'Tidak ada unit kondisi baik yang tersedia untuk dipinjamkan.';
        } else {
            const kondisi = document.getElementById('kondisiSumber').value;
            maksimal = kondisi === 'rusak_ringan' ? ringan : berat;
            qtyHint.textContent = maksimal > 0
                ? `Maksimal ${maksimal} unit pada kondisi ${kondisi.replace('_', ' ')}.`
                : `Tidak ada unit ${kondisi.replace('_', ' ')} pada barang ini.`;
        }

        qty.max = maksimal > 0 ? maksimal : 1;
        if (parseInt(qty.value || '1', 10) > maksimal && maksimal > 0) qty.value = maksimal;
        qtyHint.style.color = maksimal > 0 ? 'var(--gray)' : '#DC2626';
    }

    function openKembaliModal(id, nama, qty, tanggalKeluar) {
        document.getElementById('kembaliForm').action = `${KEMBALI_URL_BASE}/${id}/kembalikan`;
        document.getElementById('kembaliInfo').innerHTML =
            `Mengembalikan <strong>${qty} unit ${nama}</strong> ke ruangan asal. `
            + `Total unit barang tidak berubah, hanya rincian kondisinya yang disesuaikan.`;
        const input = document.getElementById('tanggalKembali');
        if (tanggalKeluar) {
            input.min = tanggalKeluar;
            document.getElementById('kembaliHint').textContent =
                'Tidak boleh mendahului tanggal keluar (' + tanggalKeluar + ').';
        }
        openModal('kembaliModal');
    }

    @if($errors->any() && old('jenis'))
        document.addEventListener('DOMContentLoaded', () => openKeluarModal(@json(old('jenis'))));
    @endif
</script>
@endunless
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
        if (!confirm('Apakah Anda yakin ingin menghapus ' + count + ' data mutasi barang keluar yang dipilih?')) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    function deleteSingleItem(id) {
        if (confirm('Apakah Anda yakin ingin menghapus catatan barang keluar ini?')) {
            const form = document.getElementById('singleDeleteForm');
            form.action = '{{ url("/mutasi") }}/' + id;
            form.submit();
        }
    }
</script>
@endsection
