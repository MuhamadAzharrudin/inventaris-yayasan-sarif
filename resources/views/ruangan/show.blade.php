@extends('layouts.admin')

@section('page-title', 'Daftar Barang — ' . ($location->nama ?? 'Ruangan'))
@section('page-icon', 'door-open')

@section('content')

{{-- ── HEADER ── --}}
<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-icon" title="Kembali ke dashboard">
                <i data-lucide="arrow-left"></i>
            </a>
            {{ $location->nama ?? 'Daftar Barang Ruangan' }}
        </h1>
        <p>
            Unit {{ $location->unit->nama ?? 'Sekolah' }} ·
            <span class="badge badge-gray">{{ strtoupper($location->tipe) }}</span> ·
            {{ $totalJenis }} jenis barang · Kode ruangan: <code>{{ $location->slug }}</code>
        </p>
    </div>

    @if(! auth()->user()->isSuperAdmin())
    <div class="page-actions">
        <a href="{{ route('aset.create', ['ruangan' => $slug]) }}" class="btn btn-green">
            <i data-lucide="plus-circle"></i> Tambah Barang
        </a>
        <a href="{{ route('aset.cetak-qr', ['ruangan' => $slug]) }}" class="btn btn-blue">
            <i data-lucide="printer"></i> Cetak Label QR
        </a>
        <a href="{{ route('aset.export-excel', ['ruangan' => $slug]) }}" class="btn btn-ghost">
            <i data-lucide="file-spreadsheet"></i> Export
        </a>
    </div>
    @endif
</div>

{{-- ── RINGKASAN KONDISI ── --}}
<div class="grid grid-stats" style="margin-bottom:20px;">
    <div class="stat">
        <div class="stat-top">
            <span class="stat-lbl">Total Barang</span>
            <span class="stat-ico" style="background:#EFF6FF;color:#2563EB;"><i data-lucide="package"></i></span>
        </div>
        <div class="stat-val">{{ number_format($totalItems) }} <small>unit</small></div>
        <div class="stat-sub">{{ $totalJenis }} jenis barang terdaftar</div>
    </div>

    <div class="stat" style="background:#F0FDF4;border-color:#BBF7D0;">
        <div class="stat-top">
            <span class="stat-lbl" style="color:#166534;">Kondisi Baik</span>
            <span class="stat-ico" style="background:#DCFCE7;color:#16A34A;"><i data-lucide="check-circle"></i></span>
        </div>
        <div class="stat-val" style="color:#15803D;">{{ number_format($totalBaik) }} <small>unit</small></div>
        <div class="stat-sub" style="color:#166534;">Siap digunakan</div>
    </div>

    <div class="stat" style="background:#FEFCE8;border-color:#FEF08A;">
        <div class="stat-top">
            <span class="stat-lbl" style="color:#854D0E;">Rusak Ringan</span>
            <span class="stat-ico" style="background:#FEF08A;color:#B45309;"><i data-lucide="wrench"></i></span>
        </div>
        <div class="stat-val" style="color:#B45309;">{{ number_format($totalRusakRingan) }} <small>unit</small></div>
        <div class="stat-sub" style="color:#854D0E;">Butuh perbaikan</div>
    </div>

    <div class="stat" style="background:#FEF2F2;border-color:#FECACA;">
        <div class="stat-top">
            <span class="stat-lbl" style="color:#991B1B;">Rusak Berat</span>
            <span class="stat-ico" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="alert-octagon"></i></span>
        </div>
        <div class="stat-val" style="color:#B91C1C;">{{ number_format($totalRusakBerat) }} <small>unit</small></div>
        <div class="stat-sub" style="color:#991B1B;">Perlu penggantian</div>
    </div>
</div>

{{-- ── TABEL INVENTARIS ── --}}
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Daftar Inventaris & QR Code Barang</h3>
        <form method="GET" action="{{ route('ruangan.show', ['ruangan' => $slug]) }}"
              style="display:flex;gap:8px;align-items:center;">
            <input type="search" name="q" class="input" value="{{ $keyword }}"
                   placeholder="Cari nama / kode barang…" style="min-width:180px;max-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search"></i></button>
            @if($keyword !== '')
                <a href="{{ route('ruangan.show', ['ruangan' => $slug]) }}" class="btn btn-soft btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="scroll-hint"><i data-lucide="move-horizontal" style="width:12px;height:12px;"></i> Geser tabel ke samping untuk melihat kolom lain</div>

    <div class="table-wrap">
        <table class="table table-wide">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Tanggal Pembaruan</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Gambar Barang</th>
                    <th style="text-align:center;">Gambar QR</th>
                    <th style="text-align:center;">Aksi / Opsi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $index => $asset)
                    @php $dipinjam = $asset->qtyDipinjam(); @endphp
                    <tr>
                        <td style="font-weight:700;color:#64748B;">{{ $index + 1 }}</td>
                        <td class="td-nowrap" style="color:#64748B;">{{ $asset->updated_at?->format('d/m/Y H:i') }}</td>
                        <td><span class="code">{{ $asset->kode_barang }}</span></td>
                        <td>
                            <div style="font-weight:700;">{{ $asset->nama_barang }}</div>
                            <div style="font-size:.75rem;color:#64748B;margin-top:2px;display:flex;gap:8px;flex-wrap:wrap;">
                                <span><i data-lucide="tag" style="width:11px;height:11px;"></i> {{ $asset->category->nama ?? 'Umum' }}</span>
                                <span><i data-lucide="bookmark" style="width:11px;height:11px;"></i> {{ $asset->merek->nama ?? 'Tanpa Merek' }}</span>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <div style="font-size:1.15rem;font-weight:800;color:#0F172A;line-height:1.1;">
                                {{ number_format($asset->total_qty) }}
                            </div>
                            <div style="font-size:.72rem;color:#64748B;">unit</div>
                            @if($dipinjam > 0)
                                <span class="badge badge-purple" style="margin-top:4px;">
                                    <i data-lucide="handshake" style="width:11px;height:11px;"></i> {{ $dipinjam }} dipinjam
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <img src="{{ $asset->fotoUrl() }}" alt="Foto {{ $asset->nama_barang }}" loading="lazy"
                                 style="width:52px;height:52px;object-fit:cover;border-radius:9px;border:1px solid #E2E8F0;">
                        </td>
                        <td style="text-align:center;">
                            <img src="{{ $asset->qrUrl() }}" alt="QR {{ $asset->kode_barang }}" loading="lazy"
                                 style="width:52px;height:52px;background:#fff;padding:2px;border:1px solid #CBD5E1;border-radius:7px;">
                        </td>
                        <td>
                            <div class="cell-actions">
                                @if(auth()->user()->isSuperAdmin())
                                    <span class="badge badge-gray">Lihat saja</span>
                                @else
                                    <button type="button" class="btn btn-amber btn-sm"
                                        onclick="openKondisiModal({{ $asset->id }}, @js($asset->nama_barang), {{ $asset->kondisi_baik }}, {{ $asset->kondisi_rusak_ringan }}, {{ $asset->kondisi_rusak_berat }}, {{ $dipinjam }})"
                                        title="Perbarui status kondisi barang">
                                        <i data-lucide="refresh-cw" style="width:13px;height:13px;"></i> Status
                                    </button>
                                    <a href="{{ route('aset.edit', $asset->id) }}" class="btn btn-blue btn-sm" title="Ubah data barang">
                                        <i data-lucide="pencil" style="width:13px;height:13px;"></i> Ubah
                                    </a>
                                    <form action="{{ route('aset.destroy', $asset->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus barang {{ addslashes($asset->nama_barang) }}? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-red btn-sm" title="Hapus barang">
                                            <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i data-lucide="package-search" style="width:38px;height:38px;stroke-width:1.4;"></i>
                                <strong>
                                    @if($keyword !== '')
                                        Tidak ada barang yang cocok dengan "{{ $keyword }}"
                                    @else
                                        Belum ada barang terdaftar di {{ $location->nama }}
                                    @endif
                                </strong>
                                <p>
                                    @if($keyword !== '')
                                        Coba kata kunci lain atau reset pencarian.
                                    @else
                                        Gunakan tombol "Tambah Barang" untuk mulai mendata inventaris ruangan ini.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($assets->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Total unit pada tampilan ini</td>
                    <td style="text-align:center;">{{ number_format($assets->sum('total_qty')) }} unit</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── MODAL PERBARUI KONDISI ── --}}
@if(! auth()->user()->isSuperAdmin())
<div class="modal" id="kondisiModal">
    <div class="modal-box">
        <form id="kondisiForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="modal-head">
                <h3 id="kondisiModalTitle">Perbarui Status Kondisi Barang</h3>
                <button type="button" class="modal-close" onclick="closeModal('kondisiModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info" style="margin:0;">
                    <i data-lucide="info"></i>
                    <div>Total unit dihitung otomatis dari rincian kondisi:
                        <strong>total = baik + rusak ringan + rusak berat</strong>.</div>
                </div>

                <div class="field">
                    <label for="inputBaik">Jumlah kondisi <strong>BAIK</strong> <span class="req">*</span></label>
                    <input type="number" class="input" name="kondisi_baik" id="inputBaik" min="0" required
                           style="color:#16A34A;font-weight:800;" oninput="hitungTotalKondisi()">
                    <span class="hint" id="hintPinjam"></span>
                </div>

                <div class="field">
                    <label for="inputRingan">Jumlah <strong>RUSAK RINGAN</strong> (butuh perbaikan) <span class="req">*</span></label>
                    <input type="number" class="input" name="kondisi_rusak_ringan" id="inputRingan" min="0" required
                           style="color:#D97706;font-weight:800;" oninput="hitungTotalKondisi()">
                </div>

                <div class="field">
                    <label for="inputBerat">Jumlah <strong>RUSAK BERAT</strong> (usul penggantian) <span class="req">*</span></label>
                    <input type="number" class="input" name="kondisi_rusak_berat" id="inputBerat" min="0" required
                           style="color:#DC2626;font-weight:800;" oninput="hitungTotalKondisi()">
                </div>

                <div class="field">
                    <label>Total unit (otomatis)</label>
                    <input type="text" class="input" id="inputTotal" readonly style="font-weight:800;">
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('kondisiModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    function hitungTotalKondisi() {
        const b = parseInt(document.getElementById('inputBaik').value || 0, 10);
        const r = parseInt(document.getElementById('inputRingan').value || 0, 10);
        const t = parseInt(document.getElementById('inputBerat').value || 0, 10);
        document.getElementById('inputTotal').value = (b + r + t) + ' unit';
    }

    function openKondisiModal(id, nama, baik, ringan, berat, dipinjam) {
        document.getElementById('kondisiModalTitle').textContent = 'Kondisi: ' + nama;
        document.getElementById('kondisiForm').action = '{{ url('ruangan/aset') }}/' + id + '/kondisi';
        document.getElementById('inputBaik').value   = baik;
        document.getElementById('inputRingan').value = ringan;
        document.getElementById('inputBerat').value  = berat;
        document.getElementById('inputBaik').min     = dipinjam;
        document.getElementById('hintPinjam').textContent = dipinjam > 0
            ? dipinjam + ' unit sedang dipinjamkan, jumlah kondisi baik tidak boleh kurang dari itu.'
            : '';
        hitungTotalKondisi();
        openModal('kondisiModal');
    }
</script>
@endsection
