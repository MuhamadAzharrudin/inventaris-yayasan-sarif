@extends('layouts.admin')

@php $isYayasan = auth()->user()->isSuperAdmin(); @endphp

@section('page-title', $isYayasan ? 'Laporan & Audit Global' : 'Cek Pelaporan')
@section('page-icon', $isYayasan ? 'file-bar-chart' : 'file-search')

@section('content')

<div class="page-head">
    <div>
        <h1>
            <i data-lucide="{{ $isYayasan ? 'file-bar-chart' : 'file-search' }}"></i>
            {{ $isYayasan ? 'Laporan & Audit Global Yayasan' : 'Cek Status Pelaporan' }}
        </h1>
        <p>
            @if($isYayasan)
                Verifikasi laporan kerusakan, penggantian, dan peminjaman barang dari seluruh unit sekolah
            @else
                Pantau status laporan yang Anda buat — verifikasi dilakukan oleh Admin Yayasan
            @endif
        </p>
    </div>
    <div class="page-actions">
        @unless($isYayasan)
            <a href="{{ route('laporan.create') }}" class="btn btn-red"><i data-lucide="plus-circle"></i> Buat Laporan</a>
        @endunless
        <a href="{{ route('laporan.selesai') }}" class="btn btn-green"><i data-lucide="check-circle-2"></i> Pelaporan Selesai</a>
        <a href="{{ route('laporan.export', request()->query()) }}" class="btn btn-ghost"><i data-lucide="download"></i> Rekap CSV</a>
    </div>
</div>

{{-- ── RINGKASAN STATUS ── --}}
<div class="grid grid-stats" style="margin-bottom:20px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Menunggu Verifikasi</span>
            <span class="stat-ico" style="background:#FEF3C7;color:#B45309;"><i data-lucide="clock"></i></span>
        </div>
        <div class="stat-val" style="color:#B45309;">{{ $counters['pending'] }}</div>
        <div class="stat-sub">Status pending</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Sedang Diproses</span>
            <span class="stat-ico" style="background:#DBEAFE;color:#2563EB;"><i data-lucide="loader"></i></span>
        </div>
        <div class="stat-val" style="color:#1E40AF;">{{ $counters['proses'] }}</div>
        <div class="stat-sub">Ditindaklanjuti Yayasan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Disetujui</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="check-circle-2"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ $counters['disetujui'] }}</div>
        <div class="stat-sub">Terverifikasi Yayasan</div>
    </div>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Ditolak</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="x-circle"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ $counters['ditolak'] }}</div>
        <div class="stat-sub">Perlu perbaikan pengajuan</div>
    </div>
</div>

{{-- ── FILTER ── --}}
<div class="card card-pad" style="margin-bottom:18px;">
    <form method="GET" action="{{ route('laporan.index') }}" class="filter-bar">
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
            <label for="status">Status</label>
            <select id="status" name="status" class="select">
                <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif (pending & proses)</option>
                <option value="pending" @selected($filters['status'] === 'pending')>Menunggu verifikasi</option>
                <option value="proses" @selected($filters['status'] === 'proses')>Sedang diproses</option>
                <option value="selesai" @selected($filters['status'] === 'selesai')>Selesai</option>
                <option value="semua" @selected($filters['status'] === 'semua')>Semua status</option>
            </select>
        </div>

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
                <option value="semua" @selected($filters['verifikasi'] === 'semua')>Semua</option>
                <option value="belum" @selected($filters['verifikasi'] === 'belum')>Belum diverifikasi</option>
                <option value="disetujui" @selected($filters['verifikasi'] === 'disetujui')>Disetujui</option>
                <option value="ditolak" @selected($filters['verifikasi'] === 'ditolak')>Ditolak</option>
            </select>
        </div>

        <div class="field">
            <label for="dari">Tanggal dari</label>
            <input type="date" id="dari" name="dari" class="input" value="{{ $filters['dari'] }}">
        </div>

        <div class="field">
            <label for="sampai">Tanggal sampai</label>
            <input type="date" id="sampai" name="sampai" class="input" value="{{ $filters['sampai'] }}">
        </div>

        <div class="field">
            <label>&nbsp;</label>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-block"><i data-lucide="filter"></i> Terapkan</button>
                <a href="{{ route('laporan.index') }}" class="btn btn-soft">Reset</a>
            </div>
        </div>
    </form>
</div>

{{-- ── DAFTAR LAPORAN ── --}}
<div style="display:flex;flex-direction:column;gap:14px;">
    @forelse($reports as $rep)
        <div class="card">
            <div class="card-head">
                <div style="min-width:0;">
                    <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:5px;">
                        <span class="badge badge-blue">{{ $rep->unit->nama ?? 'Unit' }}</span>
                        <span class="badge badge-purple">{{ $rep->jenisLabel() }}</span>
                        @if($rep->isPending())
                            <span class="badge badge-amber"><i data-lucide="clock" style="width:11px;height:11px;"></i> Menunggu Verifikasi</span>
                        @elseif($rep->isProses())
                            <span class="badge badge-blue"><i data-lucide="loader" style="width:11px;height:11px;"></i> Sedang Diproses</span>
                        @elseif($rep->isRejected())
                            <span class="badge badge-red"><i data-lucide="x-circle" style="width:11px;height:11px;"></i> Selesai — Ditolak</span>
                        @else
                            <span class="badge badge-green"><i data-lucide="check-circle-2" style="width:11px;height:11px;"></i> Selesai — Disetujui</span>
                        @endif
                    </div>
                    <h3 style="font-size:1rem;">{{ $rep->judul }}</h3>
                    <div style="font-size:.78rem;color:var(--gray);margin-top:3px;">
                        Dilaporkan oleh <strong>{{ $rep->user->name ?? 'Admin Unit' }}</strong>
                        · {{ $rep->created_at?->format('d M Y H:i') }}
                        @if($rep->qty) · {{ $rep->qty }} unit terdampak @endif
                        @if($rep->verified_at)
                            · Diverifikasi {{ $rep->verifier->name ?? 'Admin Yayasan' }}
                            pada {{ $rep->verified_at->format('d M Y H:i') }}
                        @endif
                    </div>
                </div>
                <a href="{{ route('laporan.pdf', $rep->id) }}" target="_blank" class="btn btn-ghost btn-sm">
                    <i data-lucide="printer"></i> Cetak
                </a>
            </div>

            <div class="card-pad">
                @if($rep->asset)
                    <div style="display:flex;gap:13px;align-items:center;background:var(--slate);border:1px solid var(--border);border-radius:11px;padding:11px 13px;margin-bottom:13px;flex-wrap:wrap;">
                        <img src="{{ $rep->asset->qrUrl() }}" alt="QR {{ $rep->asset->kode_barang }}" loading="lazy"
                             style="width:46px;height:46px;background:#fff;padding:2px;border:1px solid #CBD5E1;border-radius:7px;">
                        <div style="font-size:.82rem;min-width:0;">
                            <div><strong>Barang:</strong> {{ $rep->asset->nama_barang }} <span class="code">{{ $rep->asset->kode_barang }}</span></div>
                            <div style="color:var(--gray);">
                                <strong>Lokasi:</strong> {{ $rep->location->nama ?? '-' }} ·
                                <strong>Kategori/Merek:</strong> {{ $rep->asset->category->nama ?? '-' }} / {{ $rep->asset->merek->nama ?? '-' }}
                            </div>
                        </div>
                    </div>
                @endif

                <p style="font-size:.88rem;color:#334155;">{{ $rep->deskripsi }}</p>

                @if($rep->tanggapan)
                    <div class="alert {{ $rep->isRejected() ? 'alert-error' : 'alert-info' }}" style="margin:13px 0 0;">
                        <i data-lucide="message-square"></i>
                        <div><strong>Tanggapan Yayasan:</strong> {{ $rep->tanggapan }}</div>
                    </div>
                @endif

                @if($isYayasan)
                    {{-- ── FORM VERIFIKASI (khusus Admin Yayasan) ── --}}
                    <form action="{{ route('laporan.updateStatus', $rep->id) }}" method="POST"
                          style="margin-top:15px;padding-top:14px;border-top:1px dashed var(--border);">
                        @csrf
                        @method('PUT')

                        <div class="field" style="margin-bottom:11px;">
                            <label for="tanggapan-{{ $rep->id }}">
                                Tanggapan / catatan verifikasi
                                <span class="hint">(wajib bila menolak laporan)</span>
                            </label>
                            <textarea id="tanggapan-{{ $rep->id }}" name="tanggapan" class="textarea" rows="2"
                                      placeholder="Tuliskan tindak lanjut, keputusan, atau alasan penolakan…">{{ $rep->tanggapan }}</textarea>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            @unless($rep->isSelesai())
                                <button type="submit" name="aksi" value="proses" class="btn btn-blue btn-sm">
                                    <i data-lucide="loader"></i> Tandai Diproses
                                </button>
                                <button type="submit" name="aksi" value="setujui" class="btn btn-green btn-sm">
                                    <i data-lucide="check-circle-2"></i> Verifikasi & Setujui
                                </button>
                                <button type="submit" name="aksi" value="tolak" class="btn btn-red btn-sm">
                                    <i data-lucide="x-circle"></i> Tolak Laporan
                                </button>
                            @else
                                <button type="submit" name="aksi" value="buka" class="btn btn-amber btn-sm">
                                    <i data-lucide="rotate-ccw"></i> Buka Ulang Laporan
                                </button>
                            @endunless
                        </div>
                    </form>
                @else
                    {{-- ── PANTAU STATUS (Admin Unit: hanya melihat) ── --}}
                    <div style="margin-top:15px;padding-top:14px;border-top:1px dashed var(--border);display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                        <span style="font-size:.78rem;font-weight:800;color:var(--gray);text-transform:uppercase;letter-spacing:.05em;">
                            Alur verifikasi
                        </span>
                        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:.78rem;font-weight:700;">
                            <span class="badge {{ in_array($rep->status, ['pending','proses','selesai']) ? 'badge-green' : 'badge-gray' }}">1. Dikirim</span>
                            <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--gray-l);"></i>
                            <span class="badge {{ in_array($rep->status, ['proses','selesai']) ? 'badge-green' : 'badge-gray' }}">2. Diproses Yayasan</span>
                            <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--gray-l);"></i>
                            <span class="badge {{ $rep->isSelesai() ? ($rep->isRejected() ? 'badge-red' : 'badge-green') : 'badge-gray' }}">
                                3. {{ $rep->isSelesai() ? ($rep->isRejected() ? 'Ditolak' : 'Disetujui') : 'Verifikasi' }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card card-pad">
            <div class="empty-state">
                <i data-lucide="file-search" style="width:38px;height:38px;stroke-width:1.4;"></i>
                <strong>Tidak ada laporan yang cocok dengan filter</strong>
                <p>
                    @if($isYayasan)
                        Seluruh laporan unit sekolah sudah ditindaklanjuti, atau ubah filter untuk melihat data lain.
                    @else
                        Belum ada laporan pada rentang filter ini. Gunakan tombol "Buat Laporan" untuk mengajukan laporan baru.
                    @endif
                </p>
            </div>
        </div>
    @endforelse
</div>

@if($reports->hasPages())
    <div class="pagination-wrap">{{ $reports->links() }}</div>
@endif

@endsection
