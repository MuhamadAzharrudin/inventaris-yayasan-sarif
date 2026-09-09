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

{{-- ── DAFTAR ── --}}
<div style="display:flex;flex-direction:column;gap:14px;">
    @forelse($reports as $rep)
        <div class="card" style="border-color:{{ $rep->isRejected() ? '#FECACA' : '#BBF7D0' }};">
            <div class="card-head" style="background:{{ $rep->isRejected() ? '#FEF2F2' : '#F0FDF4' }};">
                <div style="min-width:0;">
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
                <a href="{{ route('laporan.pdf', $rep->id) }}" target="_blank" class="btn btn-ghost btn-sm">
                    <i data-lucide="printer"></i> Cetak PDF
                </a>
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

@endsection
