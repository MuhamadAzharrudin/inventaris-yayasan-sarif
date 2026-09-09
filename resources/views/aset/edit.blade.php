@extends('layouts.admin')

@section('page-title', 'Ubah Data Barang')
@section('page-icon', 'pencil')

@section('content')

@php $dipinjam = $asset->qtyDipinjam(); @endphp

<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('ruangan.show', ['ruangan' => $asset->location->slug ?? '']) }}" class="btn btn-ghost btn-icon" title="Kembali">
                <i data-lucide="arrow-left"></i>
            </a>
            <i data-lucide="pencil"></i> Ubah Data Barang
        </h1>
        <p>{{ $asset->nama_barang }} · <span class="code">{{ $asset->kode_barang }}</span></p>
    </div>
</div>

<div style="max-width:880px;">
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="package"></i> Formulir Perubahan Data</h3>
        <img src="{{ $asset->qrUrl() }}" alt="QR {{ $asset->kode_barang }}"
             style="width:46px;height:46px;border:1px solid var(--border);border-radius:7px;background:#fff;padding:2px;">
    </div>

    <form action="{{ route('aset.update', $asset->id) }}" method="POST" enctype="multipart/form-data"
          class="card-pad" style="display:flex;flex-direction:column;gap:16px;">
        @csrf
        @method('PUT')

        @if($dipinjam > 0)
            <div class="alert alert-info" style="margin:0;">
                <i data-lucide="handshake"></i>
                <div>{{ $dipinjam }} unit barang ini sedang dipinjamkan. Jumlah kondisi baik tidak boleh
                    kurang dari {{ $dipinjam }} unit sampai barang dikembalikan.</div>
            </div>
        @endif

        <div class="field">
            <label for="nama_barang">Nama barang <span class="req">*</span></label>
            <input type="text" id="nama_barang" name="nama_barang" class="input" required maxlength="255"
                   value="{{ old('nama_barang', $asset->nama_barang) }}">
            @error('nama_barang')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="location_id">Lokasi ruangan <span class="req">*</span></label>
                <select id="location_id" name="location_id" class="select" required>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected((int) old('location_id', $asset->location_id) === $loc->id)>
                            {{ $loc->nama }} ({{ strtoupper($loc->tipe) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="kode_barang">Kode barang <span class="req">*</span></label>
                <input type="text" id="kode_barang" name="kode_barang" class="input" required maxlength="60"
                       value="{{ old('kode_barang', $asset->kode_barang) }}"
                       style="font-family:ui-monospace,Menlo,monospace;font-weight:700;">
                <span class="hint">Mengubah kode akan memperbarui isi QR Code barang.</span>
                @error('kode_barang')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="category_id">Kategori barang</label>
                <select id="category_id" name="category_id" class="select">
                    <option value="">— Tanpa kategori —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('category_id', $asset->category_id) === $cat->id)>{{ $cat->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="merek_id">Merek / brand</label>
                <select id="merek_id" name="merek_id" class="select">
                    <option value="">— Tanpa merek —</option>
                    @foreach($mereks as $m)
                        <option value="{{ $m->id }}" @selected((int) old('merek_id', $asset->merek_id) === $m->id)>{{ $m->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── FOTO ── --}}
        <div style="background:var(--slate);border:2px dashed #CBD5E1;border-radius:12px;padding:16px;display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
            <img id="imgPreview" src="{{ $asset->fotoUrl() }}" alt="Foto barang"
                 style="width:104px;height:104px;object-fit:cover;border-radius:11px;border:1px solid var(--border);">
            <div style="min-width:0;flex:1;">
                <div style="font-size:.86rem;font-weight:800;color:var(--navy);">Ganti foto barang</div>
                <p style="font-size:.77rem;color:var(--gray);margin:4px 0 10px;">
                    Biarkan kosong bila tidak ingin mengubah foto (JPG, PNG, WEBP · maks 5 MB)
                </p>
                <input type="file" name="foto_barang" id="fotoInput" accept="image/*" capture="environment"
                       onchange="previewFoto(event)" style="display:none;">
                <button type="button" class="btn btn-blue btn-sm" onclick="document.getElementById('fotoInput').click()">
                    <i data-lucide="upload"></i> Pilih Foto Baru
                </button>
                @error('foto_barang')<div class="field-error" style="margin-top:7px;">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ── KONDISI ── --}}
        <div style="background:var(--slate);border:1px solid var(--border);border-radius:12px;padding:16px;">
            <div style="font-size:.9rem;font-weight:800;color:var(--navy);margin-bottom:5px;">
                <i data-lucide="bar-chart-3"></i> Rincian Kondisi Barang
            </div>
            <p style="font-size:.77rem;color:var(--gray);margin-bottom:13px;">
                Total unit dihitung otomatis: <strong>total = baik + rusak ringan + rusak berat</strong>
            </p>

            <div class="form-grid">
                <div class="field">
                    <label for="kondisi_baik" style="color:#166534;">Jumlah baik <span class="req">*</span></label>
                    <input type="number" id="kondisi_baik" name="kondisi_baik" class="input" min="{{ $dipinjam }}" required
                           value="{{ old('kondisi_baik', $asset->kondisi_baik) }}"
                           style="color:#16A34A;font-weight:800;" oninput="hitungTotal()">
                    @error('kondisi_baik')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="kondisi_rusak_ringan" style="color:#854D0E;">Rusak ringan <span class="req">*</span></label>
                    <input type="number" id="kondisi_rusak_ringan" name="kondisi_rusak_ringan" class="input" min="0" required
                           value="{{ old('kondisi_rusak_ringan', $asset->kondisi_rusak_ringan) }}"
                           style="color:#D97706;font-weight:800;" oninput="hitungTotal()">
                </div>
                <div class="field">
                    <label for="kondisi_rusak_berat" style="color:#991B1B;">Rusak berat <span class="req">*</span></label>
                    <input type="number" id="kondisi_rusak_berat" name="kondisi_rusak_berat" class="input" min="0" required
                           value="{{ old('kondisi_rusak_berat', $asset->kondisi_rusak_berat) }}"
                           style="color:#DC2626;font-weight:800;" oninput="hitungTotal()">
                </div>
                <div class="field">
                    <label>Total unit (otomatis)</label>
                    <input type="text" id="totalPreview" class="input" readonly style="font-weight:800;">
                </div>
            </div>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="nilai_estimasi">Nilai estimasi total (Rp)</label>
                <input type="number" id="nilai_estimasi" name="nilai_estimasi" class="input" min="0" step="1000"
                       value="{{ old('nilai_estimasi', (int) $asset->nilai_estimasi) }}">
            </div>
            <div class="field" style="grid-column:1/-1;">
                <label for="spesifikasi">Spesifikasi / catatan tambahan</label>
                <textarea id="spesifikasi" name="spesifikasi" class="textarea" rows="3" maxlength="2000">{{ old('spesifikasi', $asset->spesifikasi) }}</textarea>
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
            <a href="{{ route('ruangan.show', ['ruangan' => $asset->location->slug ?? '']) }}" class="btn btn-soft">Batal</a>
            <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>
</div>

@endsection

@section('scripts')
<script>
    function hitungTotal() {
        const b = parseInt(document.getElementById('kondisi_baik').value || 0, 10);
        const r = parseInt(document.getElementById('kondisi_rusak_ringan').value || 0, 10);
        const t = parseInt(document.getElementById('kondisi_rusak_berat').value || 0, 10);
        document.getElementById('totalPreview').value = (b + r + t) + ' unit';
    }

    function previewFoto(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => { document.getElementById('imgPreview').src = e.target.result; };
        reader.readAsDataURL(file);
    }

    document.addEventListener('DOMContentLoaded', hitungTotal);
</script>
@endsection
