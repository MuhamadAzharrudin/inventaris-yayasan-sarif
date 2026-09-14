@extends('layouts.admin')

@php $isYayasan = auth()->user()->isSuperAdmin(); @endphp

@section('page-title', $isYayasan ? 'Laporan Terverifikasi' : 'Pelaporan Selesai')
@section('page-icon', 'check-circle-2')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="check-circle-2"></i> Pelaporan Selesai & Terverifikasi</h1>
        <p>
            @if($isYayasan)
                Arsip laporan seluruh unit sekolah yang telah selesai diverifikasi Yayasan
            @else
                Daftar laporan Anda yang sudah diverifikasi Admin Yayasan beserta hasil keputusannya
            @endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('laporan.index') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Pelaporan Aktif</a>
        <a href="{{ route('laporan.export', array_merge(request()->query(), ['status' => 'selesai'])) }}" class="btn btn-green">
            <i data-lucide="download"></i> Rekap CSV
        </a>
    </div>
</div>

{{-- ── RINGKASAN ── --}}
<div class="grid grid-stats" style="margin-bottom:20px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Selesai</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="file-check-2"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ $counters['selesai'] }}</div>
        <div class="stat-sub">Laporan yang sudah diverifikasi</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Disetujui</span>
            <span class="stat-ico" style="background:#D1FAE5;color:#059669;"><i data-lucide="thumbs-up"></i></span>
        </div>
        <div class="stat-val" style="color:#047857;">{{ $counters['disetujui'] }}</div>
        <div class="stat-sub">Pengajuan diterima Yayasan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Ditolak</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="thumbs-down"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ $counters['ditolak'] }}</div>
        <div class="stat-sub">Perlu pengajuan ulang</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Masih Aktif</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#B45309;"><i data-lucide="clock"></i></span>
        </div>
        <div class="stat-val" style="color:#B45309;">{{ $counters['pending'] + $counters['proses'] }}</div>
        <div class="stat-sub">Belum selesai diverifikasi</div>
    </div>
</div>

{{-- ── FILTER ── --}}
<div class="card card-pad" style="margin-bottom:18px;">
    <form method="GET" action="{{ route('laporan.selesai') }}" class="filter-bar">
        <div class="field">
            <label for="q">Kata kunci</label>
            <input type="search" id="q" name="q" class="input" value="{{ $filters['q'] }}" placeholder="Judul / barang / kode…">
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
            <label for="jenis">Jenis laporan</label>
            <select id="jenis" name="jenis" class="select">
                <option value="semua" @selected($filters['jenis'] === 'semua')>Semua jenis</option>
                <option value="kerusakan" @selected($filters['jenis'] === 'kerusakan')>Kerusakan</option>
                <option value="penggantian" @selected($filters['jenis'] === 'penggantian')>Penggantian barang rusak</option>
                <option value="peminjaman" @selected($filters['jenis'] === 'peminjaman')>Peminjaman barang</option>
            </select>
        </div>

        <div class="field">
            <label for="verifikasi">Hasil verifikasi</label>
            <select id="verifikasi" name="verifikasi" class="select">
                <option value="semua" @selected($filters['verifikasi'] === 'semua')>Semua hasil</option>
                <option value="disetujui" @selected($filters['verifikasi'] === 'disetujui')>Disetujui</option>
                <option value="ditolak" @selected($filters['verifikasi'] === 'ditolak')>Ditolak</option>
            </select>
        </div>

        <div class="field">
            <label>&nbsp;</label>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-block"><i data-lucide="filter"></i> Terapkan</button>
                <a href="{{ route('laporan.selesai') }}" class="btn btn-soft">Reset</a>
            </div>
        </div>
    </form>
</div>

{{-- ── BULK ACTION BAR & PILIH SEMUA (KHUSUS YAYASAN) ── --}}
@if($isYayasan)
<form id="bulkDeleteForm" action="{{ route('laporan.bulkDestroy') }}" method="POST" onsubmit="return confirmBulkDelete(event);">
    @csrf
    <div id="bulkActionBar" style="display:none;background:#FEF2F2;border:1px solid #FECACA;border-radius:13px;padding:12px 18px;margin-bottom:16px;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;box-shadow:0 4px 12px rgba(220,38,38,.08);">
        <div style="font-size:.88rem;color:#991B1B;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i data-lucide="check-square" style="width:16px;height:16px;"></i>
            <span id="bulkSelectedText">0 laporan dipilih</span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" class="btn btn-soft btn-sm" onclick="clearAllSelections()">Batal Pilihan</button>
            <button type="submit" class="btn btn-red btn-sm">
                <i data-lucide="trash-2"></i> Hapus Terpilih (<span id="bulkCountBadge">0</span>)
            </button>
        </div>
    </div>

    @if($reports->count() > 0)
    <div style="margin-bottom:12px;display:flex;align-items:center;gap:8px;padding:4px 6px;">
        <label style="display:inline-flex;align-items:center;gap:8px;font-size:.85rem;font-weight:700;color:var(--navy);cursor:pointer;">
            <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" style="cursor:pointer;width:17px;height:17px;accent-color:#DC2626;">
            Pilih Semua Laporan di Halaman Ini
        </label>
    </div>
    @endif
@endif

{{-- ── DAFTAR ── --}}
<div style="display:flex;flex-direction:column;gap:14px;">
    @forelse($reports as $rep)
        <div class="card" style="border-color:{{ $rep->isRejected() ? '#FECACA' : '#BBF7D0' }};">
            <div class="card-head" style="background:{{ $rep->isRejected() ? '#FEF2F2' : '#F0FDF4' }};">
                <div style="min-width:0;display:flex;gap:12px;align-items:flex-start;">
                    @if($isYayasan)
                        <div style="padding-top:4px;">
                            <input type="checkbox" name="ids[]" value="{{ $rep->id }}" class="check-item" onchange="updateBulkState()"
                                   style="cursor:pointer;width:18px;height:18px;accent-color:#DC2626;" title="Pilih laporan ini">
                        </div>
                    @endif
                    <div style="min-width:0;flex:1;">
                        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:5px;">
                            <span class="badge {{ $rep->isRejected() ? 'badge-red' : 'badge-green' }}">
                                <i data-lucide="{{ $rep->isRejected() ? 'x-circle' : 'check-circle-2' }}" style="width:11px;height:11px;"></i>
                                {{ $rep->isRejected() ? 'DITOLAK' : 'DISETUJUI' }}
                            </span>
                            <span class="badge badge-blue">{{ $rep->unit->nama ?? 'Unit' }}</span>
                            <span class="badge badge-purple">{{ $rep->jenisLabel() }}</span>
                        </div>
                        <h3 style="font-size:1rem;">{{ $rep->judul }}</h3>
                        <div style="font-size:.78rem;color:var(--gray);margin-top:3px;">
                            Dilaporkan {{ $rep->user->name ?? 'Admin Unit' }} · {{ $rep->created_at?->format('d M Y H:i') }}
                            @if($rep->verified_at)
                                · Diverifikasi <strong>{{ $rep->verifier->name ?? 'Admin Yayasan' }}</strong>
                                pada {{ $rep->verified_at->format('d M Y H:i') }}
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <a href="{{ route('laporan.pdf', $rep->id) }}" target="_blank" class="btn btn-ghost btn-sm">
                        <i data-lucide="printer"></i> Cetak PDF
                    </a>
                    @if($isYayasan)
                        <button type="button" class="btn btn-ghost btn-sm" title="Hapus laporan" style="color:#DC2626;"
                                onclick="deleteSingleReport({{ $rep->id }})">
                            <i data-lucide="trash-2"></i> Hapus
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-pad">
                @if($rep->asset)
                    <div style="background:var(--slate);border:1px solid var(--border);border-radius:11px;padding:11px 13px;margin-bottom:12px;font-size:.82rem;">
                        <strong>Barang:</strong> {{ $rep->asset->nama_barang }}
                        <span class="code">{{ $rep->asset->kode_barang }}</span> ·
                        <strong>Lokasi:</strong> {{ $rep->location->nama ?? '-' }}
                        @if($rep->qty) · <strong>{{ $rep->qty }} unit</strong> terdampak @endif
                    </div>
                @endif

                <p style="font-size:.88rem;color:#334155;">{{ $rep->deskripsi }}</p>

                <div class="alert {{ $rep->isRejected() ? 'alert-error' : 'alert-success' }}" style="margin:13px 0 0;">
                    <i data-lucide="message-square"></i>
                    <div>
                        <strong>Hasil verifikasi Yayasan:</strong>
                        {{ $rep->tanggapan ?? ($rep->isRejected()
                            ? 'Pengajuan ditolak tanpa catatan tambahan.'
                            : 'Pengajuan disetujui dan telah selesai ditindaklanjuti.') }}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card card-pad">
            <div class="empty-state">
                <i data-lucide="inbox" style="width:38px;height:38px;stroke-width:1.4;"></i>
                <strong>Belum ada laporan yang selesai diverifikasi</strong>
                <p>Laporan yang sudah diverifikasi Admin Yayasan akan diarsipkan di halaman ini.</p>
            </div>
        </div>
    @endforelse
</div>

@if($reports->hasPages())
    <div class="pagination-wrap">{{ $reports->links() }}</div>
@endif

@if($isYayasan)
</form>

{{-- Form hapus satuan tersembunyi --}}
<form id="singleDeleteReportForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endif

@endsection

@if($isYayasan)
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
            text.textContent = count + ' laporan dipilih';
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
            alert('Pilih setidaknya satu laporan yang ingin dihapus.');
            return false;
        }
        if (!confirm('Apakah Anda yakin ingin menghapus ' + count + ' laporan terverifikasi yang dipilih?')) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    function deleteSingleReport(id) {
        if (confirm('Apakah Anda yakin ingin menghapus laporan yang sudah diverifikasi ini?')) {
            const form = document.getElementById('singleDeleteReportForm');
            form.action = '{{ url("/laporan") }}/' + id;
            form.submit();
        }
    }
</script>
@endsection
@endif
