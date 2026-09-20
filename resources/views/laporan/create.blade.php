@extends('layouts.admin')

@section('page-title', 'Buat Laporan Kerusakan')
@section('page-icon', 'file-plus')

@section('content')

<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('laporan.index') }}" class="btn btn-ghost btn-icon" title="Kembali"><i data-lucide="arrow-left"></i></a>
            <i data-lucide="file-plus"></i> Buat Laporan Kerusakan
        </h1>
        <p>Scan QR Code dengan kamera HP/laptop atau pilih barang untuk mengisi informasi aset secara otomatis</p>
    </div>
</div>

<div style="max-width:840px;">
    <div class="card">
        <div class="card-head">
            <h3><i data-lucide="clipboard-list"></i> Formulir Pelaporan</h3>
            <span class="muted">Laporan akan diverifikasi Admin Yayasan</span>
        </div>

        <form action="{{ route('laporan.store') }}" method="POST" class="card-pad" style="display:flex;flex-direction:column;gap:16px;">
            @csrf

            {{-- ── SCANNER & UPLOAD QR CODE ── --}}
            <div id="qrDropZone" style="background:var(--slate);border:2px dashed #93C5FD;border-radius:13px;padding:17px;text-align:center;transition:border-color .2s,background .2s;">
                <h4 style="font-size:.92rem;font-weight:800;color:var(--navy);display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i data-lucide="qr-code"></i> Pindai / Upload QR Code Barang
                </h4>
                <p style="font-size:.79rem;color:var(--gray);margin:6px 0 12px;">
                    Gunakan kamera langsung atau upload file gambar/foto QR Code barang untuk pengisian otomatis
                </p>

                <input type="file" id="qrFileInput" accept="image/*" style="display:none;" onchange="handleQrFileUpload(this)">

                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                    <button type="button" id="btnStartScan" class="btn btn-blue" onclick="startCameraScan()">
                        <i data-lucide="camera"></i> Buka Kamera Scan
                    </button>
                    <button type="button" id="btnUploadQr" class="btn btn-primary" onclick="document.getElementById('qrFileInput').click()">
                        <i data-lucide="upload"></i> Upload Gambar QR
                    </button>
                    <button type="button" id="btnStopScan" class="btn btn-red" style="display:none;" onclick="stopCameraScan()">
                        <i data-lucide="x"></i> Tutup Kamera
                    </button>
                </div>

                <div id="scannerContainer" style="display:none;margin-top:14px;border-radius:12px;overflow:hidden;border:2px solid var(--blue);background:#000;">
                    <div id="reader" style="width:100%;"></div>
                </div>
                <div id="scanStatus" style="font-size:.78rem;font-weight:700;margin-top:9px;"></div>
            </div>

            {{-- ── PILIH BARANG ── --}}
            <div class="field">
                <label for="assetSelect">Barang yang dilaporkan <span class="req">*</span></label>
                <select name="asset_id" id="assetSelect" class="select" required onchange="autofillAssetInfo()">
                    <option value="">— Pilih barang atau gunakan scanner kamera —</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}"
                            data-nama="{{ $asset->nama_barang }}"
                            data-kode="{{ $asset->kode_barang }}"
                            data-kategori="{{ $asset->category->nama ?? 'Umum' }}"
                            data-merek="{{ $asset->merek->nama ?? 'Tanpa Merek' }}"
                            data-lokasi="{{ $asset->location->nama ?? 'Ruangan' }}"
                            data-total="{{ $asset->total_qty }}"
                            data-baik="{{ $asset->kondisi_baik }}"
                            data-ringan="{{ $asset->kondisi_rusak_ringan }}"
                            data-berat="{{ $asset->kondisi_rusak_berat }}"
                            data-qr="{{ $asset->qrUrl() }}"
                            @selected((string) old('asset_id', request('asset')) === (string) $asset->id)>
                            [{{ $asset->kode_barang }}] {{ $asset->nama_barang }} — {{ $asset->location->nama ?? 'Ruangan' }}
                        </option>
                    @endforeach
                </select>
                @error('asset_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            {{-- ── PANEL AUTOFILL ── --}}
            <div id="autofillPanel" style="display:none;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:15px;">
                <h4 style="font-size:.78rem;font-weight:800;color:#1E40AF;text-transform:uppercase;letter-spacing:.05em;margin-bottom:11px;">
                    <i data-lucide="check-circle-2" style="width:13px;height:13px;"></i> Informasi aset terpilih
                </h4>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <img id="qrDisplay" src="" alt="QR Code barang"
                         style="width:72px;height:72px;background:#fff;padding:4px;border-radius:9px;border:1px solid #93C5FD;">
                    <div style="font-size:.85rem;line-height:1.65;min-width:0;">
                        <div><strong style="color:#1E40AF;">Nama:</strong> <span id="infoNama" style="font-weight:800;">-</span></div>
                        <div><strong style="color:#1E40AF;">Kode:</strong> <span id="infoKode" class="code">-</span></div>
                        <div><strong style="color:#1E40AF;">Kategori / Merek:</strong> <span id="infoKat">-</span> · <span id="infoMerek">-</span></div>
                        <div><strong style="color:#1E40AF;">Lokasi:</strong> <span id="infoLokasi" style="font-weight:700;color:#2563EB;">-</span></div>
                        <div><strong style="color:#1E40AF;">Kondisi tercatat:</strong>
                            <span class="badge badge-gray">Total <span id="infoTotal">0</span></span>
                            <span class="badge badge-green">Baik <span id="infoBaik">0</span></span>
                            <span class="badge badge-amber">Ringan <span id="infoRingan">0</span></span>
                            <span class="badge badge-red">Berat <span id="infoBerat">0</span></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── DETAIL LAPORAN ── --}}
            <div class="form-grid">
                <div class="field" style="grid-column:1/-1;">
                    <label for="judul">Judul pelaporan <span class="req">*</span></label>
                    <input type="text" id="judul" name="judul" class="input" required maxlength="255"
                           value="{{ old('judul') }}" placeholder="Contoh: Kerusakan kaki meja belajar siswa">
                    @error('judul')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label for="tingkat_kerusakan">Tingkat Kerusakan <span class="req">*</span></label>
                    <select id="tingkat_kerusakan" name="tingkat_kerusakan" class="select" required>
                        <option value="rusak_ringan" @selected(old('tingkat_kerusakan', 'rusak_ringan') === 'rusak_ringan')>Rusak Ringan (Dapat diperbaiki / servis)</option>
                        <option value="rusak_berat" @selected(old('tingkat_kerusakan') === 'rusak_berat')>Rusak Berat (Patah / Mati total / Perlu diganti)</option>
                    </select>
                    @error('tingkat_kerusakan')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label for="qty">Jumlah unit terdampak <span class="req">*</span></label>
                    <input type="number" id="qty" name="qty" class="input" min="1" required
                           value="{{ old('qty', 1) }}">
                    <span class="hint" id="hintQty">Tidak boleh melebihi total unit barang.</span>
                    @error('qty')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="field">
                <label for="deskripsi">Deskripsi pelaporan <span class="req">*</span></label>
                <textarea id="deskripsi" name="deskripsi" class="textarea" rows="5" required maxlength="2000"
                          placeholder="Jelaskan detail kerusakan, kondisi terkini, dan usulan tindak lanjut…">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="alert alert-info" style="margin:0;">
                <i data-lucide="info"></i>
                <div>
                    Setelah dikirim, laporan berstatus <strong>menunggu verifikasi</strong>. Anda dapat memantau
                    perkembangannya di menu <strong>Cek Pelaporan</strong>, dan hasil akhirnya di
                    <strong>Pelaporan Selesai</strong>.
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                <a href="{{ route('laporan.index') }}" class="btn btn-soft">Batal</a>
                <button type="submit" class="btn btn-red"><i data-lucide="send"></i> Kirim Pelaporan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;

    function setScanStatus(text, color) {
        const el = document.getElementById('scanStatus');
        el.textContent = text;
        el.style.color = color || 'var(--gray)';
    }

    function autofillAssetInfo() {
        const select   = document.getElementById('assetSelect');
        const selected = select.options[select.selectedIndex];
        const panel    = document.getElementById('autofillPanel');

        if (!selected || !selected.value) {
            panel.style.display = 'none';
            return;
        }

        const get = (k) => selected.getAttribute('data-' + k) || '-';
        document.getElementById('infoNama').textContent   = get('nama');
        document.getElementById('infoKode').textContent   = get('kode');
        document.getElementById('infoKat').textContent    = get('kategori');
        document.getElementById('infoMerek').textContent  = get('merek');
        document.getElementById('infoLokasi').textContent = get('lokasi');
        document.getElementById('infoTotal').textContent  = get('total');
        document.getElementById('infoBaik').textContent   = get('baik');
        document.getElementById('infoRingan').textContent = get('ringan');
        document.getElementById('infoBerat').textContent  = get('berat');
        document.getElementById('qrDisplay').src          = get('qr');

        const total = parseInt(get('total'), 10) || 1;
        const qty   = document.getElementById('qty');
        qty.max = total;
        if (parseInt(qty.value || '1', 10) > total) qty.value = total;
        document.getElementById('hintQty').textContent = 'Maksimal ' + total + ' unit (total stok barang ini).';

        panel.style.display = 'block';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function matchAndSelectAsset(decodedText) {
        const select = document.getElementById('assetSelect');
        const target = decodedText.trim().toUpperCase();
        let found = false;

        for (let i = 0; i < select.options.length; i++) {
            const opt  = select.options[i];
            const kode = (opt.getAttribute('data-kode') || '').toUpperCase();
            if (kode && (kode === target || target.includes(kode) || kode.includes(target))) {
                select.selectedIndex = i;
                autofillAssetInfo();
                found = true;
                setScanStatus('✓ QR Code berhasil dibaca: ' + opt.getAttribute('data-nama') + ' (' + opt.getAttribute('data-kode') + ')', '#16A34A');
                break;
            }
        }

        if (!found) {
            setScanStatus('QR Code terbaca "' + decodedText + '", namun tidak cocok dengan data barang yang tersedia.', '#DC2626');
        }

        return found;
    }

    async function handleQrFileUpload(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];

        if (typeof Html5Qrcode === 'undefined') {
            setScanStatus('Pustaka scanner gagal dimuat. Periksa koneksi internet Anda.', '#DC2626');
            return;
        }

        setScanStatus('Memproses file gambar QR Code: ' + file.name + '…', '#2563EB');

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('reader');
        }

        try {
            // Tutup kamera jika sedang menyala
            if (html5QrCode.isScanning) {
                await html5QrCode.stop();
                document.getElementById('scannerContainer').style.display = 'none';
                document.getElementById('btnStartScan').style.display = 'inline-flex';
                document.getElementById('btnStopScan').style.display = 'none';
            }

            const decodedText = await html5QrCode.scanFile(file, true);
            matchAndSelectAsset(decodedText);
        } catch (err) {
            setScanStatus('QR Code tidak terdeteksi pada file gambar "' + file.name + '". Pastikan gambar QR jelas dan tidak buram.', '#DC2626');
        } finally {
            input.value = '';
        }
    }

    function startCameraScan() {
        if (typeof Html5Qrcode === 'undefined') {
            setScanStatus('Pustaka scanner gagal dimuat. Periksa koneksi internet Anda.', '#DC2626');
            return;
        }

        document.getElementById('scannerContainer').style.display = 'block';
        document.getElementById('btnStartScan').style.display = 'none';
        document.getElementById('btnStopScan').style.display = 'inline-flex';
        setScanStatus('Mengaktifkan kamera…', '#2563EB');

        if (!html5QrCode) html5QrCode = new Html5Qrcode('reader');

        html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (decodedText) => {
                matchAndSelectAsset(decodedText);
                stopCameraScan();
            },
            () => { /* frame tanpa QR: abaikan */ }
        ).catch((err) => {
            setScanStatus('Kamera tidak dapat diakses: ' + err, '#DC2626');
            stopCameraScan();
        });
    }

    function stopCameraScan() {
        const reset = () => {
            document.getElementById('scannerContainer').style.display = 'none';
            document.getElementById('btnStartScan').style.display = 'inline-flex';
            document.getElementById('btnStopScan').style.display = 'none';
        };

        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(reset).catch(reset);
        } else {
            reset();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        autofillAssetInfo();

        const dropZone = document.getElementById('qrDropZone');
        if (dropZone) {
            ['dragenter', 'dragover'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '#2563EB';
                    dropZone.style.background = '#EFF6FF';
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '#93C5FD';
                    dropZone.style.background = 'var(--slate)';
                });
            });
            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    const fileInput = document.getElementById('qrFileInput');
                    fileInput.files = dt.files;
                    handleQrFileUpload(fileInput);
                }
            });
        }
    });
</script>
@endsection
