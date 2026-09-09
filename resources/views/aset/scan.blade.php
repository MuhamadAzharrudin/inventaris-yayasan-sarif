@extends('layouts.admin')

@section('page-title', 'Scan QR Code Barang')
@section('page-icon', 'scan-line')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="scan-line"></i> Scan QR Code Barang</h1>
        <p>Pindai label QR pada fisik barang memakai kamera HP / laptop, atau masukkan kode barang secara manual</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('aset.cetak-label') }}" class="btn btn-ghost"><i data-lucide="printer"></i> Cetak Label QR</a>
    </div>
</div>

<div class="grid" style="grid-template-columns:minmax(280px,1fr) minmax(300px,1.2fr);align-items:start;">

    {{-- ══ PANEL SCANNER ══ --}}
    <div class="card">
        <div class="card-head"><h3><i data-lucide="camera"></i> Kamera Pemindai</h3></div>
        <div class="card-pad" style="display:flex;flex-direction:column;gap:14px;">

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" id="btnStartScan" class="btn btn-blue" onclick="startCameraScan()">
                    <i data-lucide="qr-code"></i> Buka Kamera
                </button>
                <button type="button" id="btnStopScan" class="btn btn-red" style="display:none;" onclick="stopCameraScan()">
                    <i data-lucide="x"></i> Tutup Kamera
                </button>
            </div>

            <div id="scannerContainer" style="display:none;border-radius:12px;overflow:hidden;border:2px solid var(--blue);background:#000;">
                <div id="reader" style="width:100%;"></div>
            </div>

            <div id="scanStatus" class="alert alert-info" style="margin:0;">
                <i data-lucide="info"></i>
                <div>Kamera membutuhkan izin peramban. Pada perangkat non-HTTPS, gunakan
                    <code>localhost</code> atau aktifkan HTTPS agar kamera dapat diakses.</div>
            </div>

            <div style="border-top:1px dashed var(--border);padding-top:14px;">
                <form id="manualForm" onsubmit="return cariManual(event)" style="display:flex;flex-direction:column;gap:9px;">
                    <div class="field">
                        <label for="kodeManual">Cari manual dengan kode barang</label>
                        <input type="text" id="kodeManual" class="input" placeholder="Contoh: MI-K1-MJ50" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> Cari Barang</button>
                </form>
            </div>

            <div>
                <div style="font-size:.76rem;font-weight:800;color:var(--gray);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
                    Kode barang terdaftar ({{ $assets->count() }})
                </div>
                <div style="max-height:200px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;">
                    @forelse($assets as $a)
                        <button type="button" class="btn btn-ghost btn-sm" style="justify-content:space-between;"
                                onclick="lookupKode(@js($a->kode_barang))">
                            <span class="code">{{ $a->kode_barang }}</span>
                            <span style="font-weight:600;font-size:.75rem;color:var(--gray);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $a->nama_barang }}
                            </span>
                        </button>
                    @empty
                        <div style="font-size:.8rem;color:var(--gray-l);">Belum ada barang terdaftar pada unit Anda.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ══ PANEL HASIL ══ --}}
    <div class="card" id="resultCard">
        <div class="card-head">
            <h3><i data-lucide="package-search"></i> Hasil Pemindaian</h3>
            <span class="muted" id="resultTime"></span>
        </div>
        <div class="card-pad" id="resultBody">
            <div class="empty-state">
                <i data-lucide="scan-line" style="width:38px;height:38px;stroke-width:1.4;"></i>
                <strong>Belum ada barang dipindai</strong>
                <p>Arahkan kamera ke label QR barang, atau pilih kode barang di panel sebelah.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    @media (max-width: 900px) {
        .grid[style*="minmax(280px,1fr)"] { grid-template-columns: 1fr !important; }
    }
    .detail-row {
        display: flex; justify-content: space-between; gap: 12px;
        padding: 9px 0; border-bottom: 1px dashed var(--border); font-size: .84rem;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-row span:first-child { color: var(--gray); font-weight: 600; }
    .detail-row span:last-child { font-weight: 700; text-align: right; }
</style>
@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    const LOOKUP_URL = @json(route('aset.lookup'));
    let html5QrCode = null;

    function setStatus(html, tipe) {
        const el = document.getElementById('scanStatus');
        el.className = 'alert ' + (tipe === 'error' ? 'alert-error' : (tipe === 'ok' ? 'alert-success' : 'alert-info'));
        el.innerHTML = '<i data-lucide="' + (tipe === 'error' ? 'alert-triangle' : (tipe === 'ok' ? 'check-circle-2' : 'info')) + '"></i><div>' + html + '</div>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function escapeHtml(t) {
        return String(t ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s]);
    }

    function renderAsset(a) {
        document.getElementById('resultTime').textContent = 'Dipindai ' + new Date().toLocaleTimeString('id-ID');
        document.getElementById('resultBody').innerHTML = `
            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;margin-bottom:14px;">
                <img src="${escapeHtml(a.foto)}" alt="Foto barang"
                     style="width:96px;height:96px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
                <div style="min-width:0;flex:1;">
                    <div style="font-size:1.05rem;font-weight:800;color:var(--ink);">${escapeHtml(a.nama_barang)}</div>
                    <div style="margin:5px 0;"><span class="code">${escapeHtml(a.kode_barang)}</span></div>
                    <div style="font-size:.8rem;color:var(--gray);">
                        ${escapeHtml(a.kategori)} · ${escapeHtml(a.merek)}
                    </div>
                    <div style="margin-top:7px;display:flex;gap:6px;flex-wrap:wrap;">
                        <span class="badge badge-gray">Unit ${escapeHtml(a.unit)}</span>
                        <span class="badge badge-blue">${escapeHtml(a.lokasi)}</span>
                    </div>
                </div>
                <img src="${escapeHtml(a.qr)}" alt="QR Code"
                     style="width:84px;height:84px;background:#fff;padding:3px;border:1px solid #CBD5E1;border-radius:9px;">
            </div>

            <div style="display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));margin-bottom:14px;">
                <div class="stat" style="padding:11px 13px;">
                    <div class="stat-lbl">Total Unit</div>
                    <div style="font-size:1.2rem;font-weight:800;">${a.total_qty}</div>
                </div>
                <div class="stat" style="padding:11px 13px;background:#F0FDF4;border-color:#BBF7D0;">
                    <div class="stat-lbl" style="color:#166534;">Baik</div>
                    <div style="font-size:1.2rem;font-weight:800;color:#15803D;">${a.baik}</div>
                </div>
                <div class="stat" style="padding:11px 13px;background:#FEFCE8;border-color:#FEF08A;">
                    <div class="stat-lbl" style="color:#854D0E;">Rusak Ringan</div>
                    <div style="font-size:1.2rem;font-weight:800;color:#B45309;">${a.rusak_ringan}</div>
                </div>
                <div class="stat" style="padding:11px 13px;background:#FEF2F2;border-color:#FECACA;">
                    <div class="stat-lbl" style="color:#991B1B;">Rusak Berat</div>
                    <div style="font-size:1.2rem;font-weight:800;color:#B91C1C;">${a.rusak_berat}</div>
                </div>
            </div>

            <div class="detail-row"><span>Sedang dipinjam</span><span>${a.dipinjam} unit</span></div>
            <div class="detail-row"><span>Nilai estimasi</span><span>${escapeHtml(a.nilai_estimasi)}</span></div>
            <div class="detail-row"><span>Spesifikasi</span><span>${escapeHtml(a.spesifikasi || '-')}</span></div>
            <div class="detail-row"><span>Terakhir diperbarui</span><span>${escapeHtml(a.updated_at || '-')}</span></div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                ${a.url_ruangan ? `<a href="${escapeHtml(a.url_ruangan)}" class="btn btn-primary btn-sm"><i data-lucide="door-open"></i> Buka Ruangan</a>` : ''}
                <a href="${escapeHtml(a.url_edit)}" class="btn btn-blue btn-sm"><i data-lucide="pencil"></i> Ubah Data</a>
                <a href="${escapeHtml(a.url_laporan)}" class="btn btn-red btn-sm"><i data-lucide="file-plus"></i> Buat Laporan</a>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function lookupKode(kode) {
        setStatus('Mencari barang dengan kode <strong>' + escapeHtml(kode) + '</strong>…', 'info');
        try {
            const res  = await fetch(LOOKUP_URL + '?kode=' + encodeURIComponent(kode), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (data.found) {
                setStatus('Barang ditemukan: <strong>' + escapeHtml(data.asset.nama_barang) + '</strong>', 'ok');
                renderAsset(data.asset);
            } else {
                setStatus(escapeHtml(data.message || 'Barang tidak ditemukan.'), 'error');
            }
        } catch (e) {
            setStatus('Gagal menghubungi server. Periksa koneksi Anda lalu coba lagi.', 'error');
        }
    }

    function cariManual(e) {
        e.preventDefault();
        const kode = document.getElementById('kodeManual').value.trim();
        if (kode === '') {
            setStatus('Masukkan kode barang terlebih dahulu.', 'error');
            return false;
        }
        lookupKode(kode);
        return false;
    }

    function startCameraScan() {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('Pustaka scanner gagal dimuat. Periksa koneksi internet Anda.', 'error');
            return;
        }

        document.getElementById('scannerContainer').style.display = 'block';
        document.getElementById('btnStartScan').style.display = 'none';
        document.getElementById('btnStopScan').style.display = 'inline-flex';
        setStatus('Mengaktifkan kamera… arahkan ke label QR barang.', 'info');

        if (!html5QrCode) html5QrCode = new Html5Qrcode('reader');

        html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (decodedText) => {
                stopCameraScan();
                lookupKode(decodedText);
            },
            () => { /* frame tanpa QR: abaikan */ }
        ).catch((err) => {
            setStatus('Kamera tidak dapat diakses: ' + escapeHtml(String(err)), 'error');
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
</script>
@endsection
