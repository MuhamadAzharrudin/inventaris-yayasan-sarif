@extends('layouts.admin')

@section('page-title', 'Cetak Label QR Barang')
@section('page-icon', 'printer')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="printer"></i> Cetak Label QR Barang</h1>
        <p>Pilih ruangan / kelas, lalu tentukan barang mana saja yang label QR-nya akan dicetak</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('aset.scan') }}" class="btn btn-ghost"><i data-lucide="scan-line"></i> Scan QR Code</a>
    </div>
</div>

{{-- ── LANGKAH 1: PILIH RUANGAN ── --}}
<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <h3><span class="badge badge-blue">Langkah 1</span> Pilih Ruangan / Kelas</h3>
        <span class="muted">{{ $locations->count() }} ruangan tersedia</span>
    </div>
    <div class="card-pad">
        <form method="GET" action="{{ route('aset.cetak-label') }}" class="filter-bar" style="margin-bottom:14px;">
            <div class="field" style="grid-column:span 2;">
                <label for="ruangan">Ruangan / kelas</label>
                <select id="ruangan" name="ruangan" class="select" onchange="this.form.submit()">
                    <option value="">— Pilih ruangan —</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->slug }}" @selected($selected && $selected->id === $loc->id)>
                            {{ $loc->nama }} ({{ strtoupper($loc->tipe) }}) — {{ $loc->assets_count }} jenis barang
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block"><i data-lucide="search"></i> Tampilkan Barang</button>
            </div>
        </form>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @foreach($locations as $loc)
                <a href="{{ route('aset.cetak-label', ['ruangan' => $loc->slug]) }}"
                   class="btn {{ $selected && $selected->id === $loc->id ? 'btn-primary' : 'btn-ghost' }} btn-sm">
                    <i data-lucide="door-open" style="width:13px;height:13px;"></i>
                    {{ $loc->nama }}
                    <span class="badge {{ $selected && $selected->id === $loc->id ? 'badge-gray' : 'badge-blue' }}">{{ $loc->assets_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ── LANGKAH 2: PILIH BARANG ── --}}
@if($selected)
    <form method="GET" action="{{ route('aset.cetak-label.preview') }}" target="_blank">
        <input type="hidden" name="ruangan" value="{{ $selected->slug }}">

        <div class="card">
            <div class="card-head">
                <h3><span class="badge badge-blue">Langkah 2</span> Pilih Barang di {{ $selected->nama }}</h3>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <label style="font-size:.8rem;font-weight:700;color:var(--gray);display:flex;align-items:center;gap:6px;">
                        Label per baris
                        <select name="per_baris" class="select" style="width:auto;padding:6px 9px;">
                            <option value="2">2</option>
                            <option value="3" selected>3</option>
                            <option value="4">4</option>
                        </select>
                    </label>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleSemua(true)">Pilih semua</button>
                    <button type="button" class="btn btn-soft btn-sm" onclick="toggleSemua(false)">Kosongkan</button>
                </div>
            </div>

            <div class="card-pad">
                @if($assets->isEmpty())
                    <div class="empty-state">
                        <i data-lucide="package-search" style="width:38px;height:38px;stroke-width:1.4;"></i>
                        <strong>Belum ada barang di {{ $selected->nama }}</strong>
                        <p>Tambahkan barang lewat menu ruangan terlebih dahulu, lalu kembali untuk mencetak labelnya.</p>
                        <a href="{{ route('aset.create', ['ruangan' => $selected->slug]) }}" class="btn btn-green btn-sm" style="margin-top:10px;">
                            <i data-lucide="plus-circle"></i> Tambah Barang
                        </a>
                    </div>
                @else
                    @error('aset')
                        <div class="alert alert-error"><i data-lucide="alert-triangle"></i><div>{{ $message }}</div></div>
                    @enderror

                    <div class="grid grid-cards" id="asetList">
                        @foreach($assets as $asset)
                            <label class="card" style="padding:13px;display:flex;gap:11px;align-items:flex-start;cursor:pointer;box-shadow:none;">
                                <input type="checkbox" name="aset[]" value="{{ $asset->id }}" checked
                                       style="width:17px;height:17px;accent-color:#2563EB;margin-top:2px;flex-shrink:0;">
                                <img src="{{ $asset->qrUrl() }}" alt="QR {{ $asset->kode_barang }}" loading="lazy"
                                     style="width:52px;height:52px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;padding:2px;flex-shrink:0;">
                                <span style="min-width:0;">
                                    <span style="display:block;font-weight:700;font-size:.86rem;">{{ $asset->nama_barang }}</span>
                                    <span style="display:block;margin:3px 0;"><span class="code">{{ $asset->kode_barang }}</span></span>
                                    <span style="display:block;font-size:.74rem;color:var(--gray);">
                                        {{ $asset->category->nama ?? 'Umum' }} · {{ $asset->merek->nama ?? 'Tanpa Merek' }}
                                        · {{ $asset->total_qty }} unit
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;flex-wrap:wrap;">
                        <a href="{{ route('aset.export-excel', ['ruangan' => $selected->slug]) }}" class="btn btn-ghost">
                            <i data-lucide="file-spreadsheet"></i> Export Data CSV
                        </a>
                        <button type="submit" class="btn btn-blue">
                            <i data-lucide="printer"></i> Pratinjau & Cetak Label
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </form>
@else
    <div class="card card-pad">
        <div class="empty-state">
            <i data-lucide="mouse-pointer-click" style="width:38px;height:38px;stroke-width:1.4;"></i>
            <strong>Pilih ruangan terlebih dahulu</strong>
            <p>Setelah ruangan dipilih, daftar barang beserta QR Code-nya akan muncul untuk dipilih.</p>
        </div>
    </div>
@endif

@endsection

@section('scripts')
<script>
    function toggleSemua(state) {
        document.querySelectorAll('#asetList input[type="checkbox"]').forEach(cb => cb.checked = state);
    }
</script>
@endsection
