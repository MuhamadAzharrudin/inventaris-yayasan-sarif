@extends('layouts.admin')

@section('page-title', 'Tambah Barang — ' . ($location->nama ?? 'Ruangan'))
@section('page-icon', 'plus-circle')

@section('content')

<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('ruangan.show', ['ruangan' => $slug]) }}" class="btn btn-ghost btn-icon" title="Kembali">
                <i data-lucide="arrow-left"></i>
            </a>
            <i data-lucide="plus-circle"></i> Tambah Barang / Unit Baru
        </h1>
        <p>Lengkapi data barang, unggah foto dari galeri atau kamera perangkat, lalu QR Code dibuat otomatis</p>
    </div>
</div>

<div style="max-width:880px;">
<div class="card">
    <div class="card-head">
        <h3><i data-lucide="package-plus"></i> Formulir Data Barang</h3>
        <span class="muted">Ruangan tujuan: {{ $location->nama }}</span>
    </div>

    <form action="{{ route('aset.store') }}" method="POST" enctype="multipart/form-data"
          class="card-pad" style="display:flex;flex-direction:column;gap:16px;">
        @csrf

        <div class="field">
            <label for="nama_barang">Nama barang / aset sarpras <span class="req">*</span></label>
            <input type="text" id="nama_barang" name="nama_barang" class="input" required maxlength="255"
                   value="{{ old('nama_barang') }}" placeholder="Contoh: Meja Belajar Siswa, Proyektor Epson, PC Lab">
            @error('nama_barang')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:15px;">
            <div class="field">
                <label for="location_id" style="color:#1E40AF;">
                    <i data-lucide="map-pin" style="width:13px;height:13px;"></i>
                    Lokasi tempat barang ditaruh <span class="req">*</span>
                </label>
                <select id="location_id" name="location_id" class="select" required style="font-weight:700;">
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected((int) old('location_id', $location->id) === $loc->id)>
                            {{ $loc->nama }} ({{ strtoupper($loc->tipe) }})
                        </option>
                    @endforeach
                </select>
                <span class="hint" style="color:#1D4ED8;">Pilih ruangan kelas, laboratorium, aula, atau kantor tempat fisik barang berada.</span>
                @error('location_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="category_id">Kategori barang</label>
                <div style="display:flex;gap:8px;align-items:stretch;">
                    <select id="category_id" name="category_id" class="select" style="flex:1;min-width:0;">
                        <option value="">— Pilih kategori —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((int) old('category_id') === $cat->id)>{{ $cat->nama }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-ghost" style="flex-shrink:0;padding:0 12px;"
                            onclick="openQuickAdd('kategori')" title="Tambah kategori baru">
                        <i data-lucide="plus"></i>
                    </button>
                </div>
                <span class="hint">Belum ada kategorinya? Tambahkan langsung dengan tombol +.</span>
            </div>

            <div class="field">
                <label for="merek_id">Merek / brand</label>
                <div style="display:flex;gap:8px;align-items:stretch;">
                    <select id="merek_id" name="merek_id" class="select" style="flex:1;min-width:0;">
                        <option value="">— Pilih merek —</option>
                        @foreach($mereks as $m)
                            <option value="{{ $m->id }}" @selected((int) old('merek_id') === $m->id)>{{ $m->nama }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-ghost" style="flex-shrink:0;padding:0 12px;"
                            onclick="openQuickAdd('merek')" title="Tambah merek baru">
                        <i data-lucide="plus"></i>
                    </button>
                </div>
                <span class="hint">Belum ada mereknya? Tambahkan langsung dengan tombol +.</span>
            </div>

            <div class="field">
                <label for="kode_barang">Kode barang (opsional)</label>
                <input type="text" id="kode_barang" name="kode_barang" class="input" maxlength="60"
                       value="{{ old('kode_barang') }}" placeholder="Otomatis bila dikosongkan"
                       style="font-family:ui-monospace,Menlo,monospace;">
                <span class="hint">Kode ini menjadi isi QR Code barang.</span>
                @error('kode_barang')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="nilai_estimasi">Nilai estimasi total (Rp)</label>
                <input type="number" id="nilai_estimasi" name="nilai_estimasi" class="input" min="0" step="1000"
                       value="{{ old('nilai_estimasi', 0) }}">
            </div>
        </div>

        {{-- ── FOTO BARANG ── --}}
        <div style="background:var(--slate);border:2px dashed #CBD5E1;border-radius:12px;padding:18px;text-align:center;">
            <div style="font-size:.88rem;font-weight:800;color:var(--navy);">
                <i data-lucide="image"></i> Foto / Dokumen Barang
            </div>
            <p style="font-size:.78rem;color:var(--gray);margin:5px 0 12px;">
                Pilih berkas dari galeri atau ambil langsung dengan kamera perangkat (JPG, PNG, WEBP · maks 5 MB)
            </p>

            <input type="file" name="foto_barang" id="fotoInput" accept="image/*" capture="environment"
                   onchange="previewFoto(event)" style="display:none;">

            <button type="button" class="btn btn-blue" onclick="document.getElementById('fotoInput').click()">
                <i data-lucide="upload"></i> Pilih File / Ambil Foto
            </button>

            <div id="previewContainer" style="display:none;margin-top:13px;">
                <img id="imgPreview" src="" alt="Pratinjau foto barang"
                     style="max-width:180px;max-height:180px;border-radius:10px;border:2px solid var(--blue);object-fit:cover;">
                <div style="font-size:.75rem;color:#059669;font-weight:700;margin-top:5px;" id="fileName"></div>
            </div>
            @error('foto_barang')<div class="field-error" style="margin-top:8px;">{{ $message }}</div>@enderror
        </div>

        {{-- ── RINCIAN KONDISI ── --}}
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
                    <input type="number" id="kondisi_baik" name="kondisi_baik" class="input" min="0" required
                           value="{{ old('kondisi_baik', 1) }}" style="color:#16A34A;font-weight:800;" oninput="hitungTotal()">
                    @error('kondisi_baik')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="kondisi_rusak_ringan" style="color:#854D0E;">Rusak ringan <span class="req">*</span></label>
                    <input type="number" id="kondisi_rusak_ringan" name="kondisi_rusak_ringan" class="input" min="0" required
                           value="{{ old('kondisi_rusak_ringan', 0) }}" style="color:#D97706;font-weight:800;" oninput="hitungTotal()">
                </div>
                <div class="field">
                    <label for="kondisi_rusak_berat" style="color:#991B1B;">Rusak berat <span class="req">*</span></label>
                    <input type="number" id="kondisi_rusak_berat" name="kondisi_rusak_berat" class="input" min="0" required
                           value="{{ old('kondisi_rusak_berat', 0) }}" style="color:#DC2626;font-weight:800;" oninput="hitungTotal()">
                </div>
                <div class="field">
                    <label for="total_qty">Total unit (otomatis)</label>
                    <input type="number" id="total_qty" name="total_qty" class="input" readonly
                           value="{{ old('total_qty', 1) }}" style="font-weight:800;">
                    @error('total_qty')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="field">
            <label for="spesifikasi">Spesifikasi / catatan tambahan</label>
            <textarea id="spesifikasi" name="spesifikasi" class="textarea" rows="3" maxlength="2000"
                      placeholder="Detail dimensi, bahan, nomor seri…">{{ old('spesifikasi') }}</textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
            <a href="{{ route('ruangan.show', ['ruangan' => $slug]) }}" class="btn btn-soft">Batal</a>
            <button type="submit" class="btn btn-green"><i data-lucide="qr-code"></i> Simpan & Generate QR</button>
        </div>
    </form>
</div>
</div>

{{-- ══ MODAL TAMBAH KATEGORI / MEREK (tanpa meninggalkan form) ══ --}}
<div class="modal" id="quickAddModal">
    <div class="modal-box" style="max-width:460px;">
        <div class="modal-head">
            <h3 id="quickAddTitle">Tambah Kategori Barang</h3>
            <button type="button" class="modal-close" onclick="closeModal('quickAddModal')">✕</button>
        </div>

        <div class="modal-body">
            <div class="alert alert-info" style="margin:0;">
                <i data-lucide="info"></i>
                <div id="quickAddInfo">
                    Data baru langsung tersimpan ke master data dan otomatis terpilih pada form barang.
                </div>
            </div>

            <div class="alert alert-error" id="quickAddError" style="display:none;margin:0;">
                <i data-lucide="alert-triangle"></i>
                <div id="quickAddErrorText"></div>
            </div>

            <div class="field">
                <label for="quickNama">Nama <span class="req">*</span></label>
                <input type="text" id="quickNama" class="input" maxlength="255" autocomplete="off"
                       placeholder="Contoh: Elektronik & IT">
            </div>

            <div class="field">
                <label for="quickKode">Kode singkat</label>
                <input type="text" id="quickKode" class="input" maxlength="20"
                       placeholder="Otomatis bila dikosongkan" style="text-transform:uppercase;">
            </div>

            <div class="field">
                <label for="quickDeskripsi">Deskripsi</label>
                <textarea id="quickDeskripsi" class="textarea" rows="2" maxlength="500"
                          placeholder="Keterangan singkat (opsional)"></textarea>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="btn btn-soft" onclick="closeModal('quickAddModal')">Batal</button>
            <button type="button" class="btn btn-primary" id="quickAddSubmit" onclick="submitQuickAdd()">
                <i data-lucide="save"></i> Simpan
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    /* ══ Tambah kategori / merek langsung dari form barang ══ */
    const QUICK_ADD = {
        kategori: {
            url: @json(route('categories.store')),
            select: 'category_id',
            judul: 'Tambah Kategori Barang',
            info: 'Kategori baru langsung tersimpan ke master data dan otomatis terpilih pada form barang.',
            placeholder: 'Contoh: Elektronik & IT',
            payloadKey: 'kategori',
        },
        merek: {
            url: @json(route('merek.store')),
            select: 'merek_id',
            judul: 'Tambah Merek / Brand',
            info: 'Merek baru langsung tersimpan ke master data dan otomatis terpilih pada form barang.',
            placeholder: 'Contoh: Epson',
            payloadKey: 'merek',
        },
    };

    let quickAddMode = 'kategori';

    function openQuickAdd(mode) {
        quickAddMode = QUICK_ADD[mode] ? mode : 'kategori';
        const conf = QUICK_ADD[quickAddMode];

        document.getElementById('quickAddTitle').textContent = conf.judul;
        document.getElementById('quickAddInfo').textContent   = conf.info;
        document.getElementById('quickNama').placeholder       = conf.placeholder;
        document.getElementById('quickNama').value             = '';
        document.getElementById('quickKode').value             = '';
        document.getElementById('quickDeskripsi').value        = '';
        hideQuickAddError();

        openModal('quickAddModal');
        setTimeout(() => document.getElementById('quickNama').focus(), 120);
    }

    function showQuickAddError(pesan) {
        document.getElementById('quickAddErrorText').textContent = pesan;
        document.getElementById('quickAddError').style.display = 'flex';
    }

    function hideQuickAddError() {
        document.getElementById('quickAddError').style.display = 'none';
        document.getElementById('quickAddErrorText').textContent = '';
    }

    async function submitQuickAdd() {
        const conf   = QUICK_ADD[quickAddMode];
        const nama   = document.getElementById('quickNama').value.trim();
        const kode   = document.getElementById('quickKode').value.trim();
        const desk   = document.getElementById('quickDeskripsi').value.trim();
        const tombol = document.getElementById('quickAddSubmit');

        hideQuickAddError();

        if (nama === '') {
            showQuickAddError('Nama wajib diisi terlebih dahulu.');
            document.getElementById('quickNama').focus();
            return;
        }

        tombol.disabled = true;
        const labelAsli = tombol.innerHTML;
        tombol.textContent = 'Menyimpan…';

        try {
            const res = await fetch(conf.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ nama: nama, kode: kode, deskripsi: desk }),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const pesan = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message || 'Data gagal disimpan. Periksa kembali isian Anda.');
                showQuickAddError(pesan);
                return;
            }

            const baru   = data[conf.payloadKey];
            const select = document.getElementById(conf.select);
            const opsi   = document.createElement('option');
            opsi.value       = baru.id;
            opsi.textContent = baru.nama;
            select.appendChild(opsi);
            select.value = String(baru.id);

            closeModal('quickAddModal');
        } catch (e) {
            showQuickAddError('Tidak dapat menghubungi server. Periksa koneksi Anda lalu coba lagi.');
        } finally {
            tombol.disabled  = false;
            tombol.innerHTML = labelAsli;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    /* Enter pada kolom nama = simpan */
    document.addEventListener('DOMContentLoaded', () => {
        ['quickNama', 'quickKode'].forEach((id) => {
            document.getElementById(id).addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); submitQuickAdd(); }
            });
        });
    });

    function hitungTotal() {
        const b = parseInt(document.getElementById('kondisi_baik').value || 0, 10);
        const r = parseInt(document.getElementById('kondisi_rusak_ringan').value || 0, 10);
        const t = parseInt(document.getElementById('kondisi_rusak_berat').value || 0, 10);
        document.getElementById('total_qty').value = b + r + t;
    }

    function previewFoto(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('previewContainer').style.display = 'block';
            document.getElementById('fileName').textContent = '✓ ' + file.name;
        };
        reader.readAsDataURL(file);
    }

    document.addEventListener('DOMContentLoaded', hitungTotal);
</script>
@endsection
