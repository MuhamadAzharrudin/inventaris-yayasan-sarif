{{-- Halaman label QR siap cetak (Ctrl+P / Simpan sebagai PDF dari peramban) --}}
@php $kolom = in_array((int) ($perBaris ?? 3), [2, 3, 4], true) ? (int) $perBaris : 3; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label QR — {{ $location->nama ?? 'Ruangan' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #F1F5F9; color: #0F172A; padding: 18px; }
        .toolbar {
            max-width: 1000px; margin: 0 auto 18px; background: #fff; border: 1px solid #E2E8F0;
            border-radius: 12px; padding: 14px 18px; display: flex; align-items: center;
            justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .toolbar h1 { font-size: 1rem; font-weight: 800; }
        .toolbar p { font-size: .8rem; color: #64748B; margin-top: 2px; }
        .toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px;
            border-radius: 9px; border: 1px solid transparent; font-size: .82rem; font-weight: 700;
            text-decoration: none; cursor: pointer; font-family: inherit;
        }
        .btn-print { background: #2563EB; color: #fff; }
        .btn-back { background: #fff; color: #1E3A5F; border-color: #E2E8F0; }

        .sheet {
            max-width: 1000px; margin: 0 auto; background: #fff; padding: 18px;
            border: 1px solid #E2E8F0; border-radius: 12px;
        }
        .sheet-head {
            text-align: center; border-bottom: 2px solid #0F172A;
            padding-bottom: 11px; margin-bottom: 16px;
        }
        .sheet-head h2 { font-size: 1.05rem; font-weight: 800; text-transform: uppercase; letter-spacing: .02em; }
        .sheet-head p { font-size: .8rem; color: #475569; margin-top: 3px; }

        .labels { display: grid; grid-template-columns: repeat({{ $kolom }}, 1fr); gap: 10px; }
        .label {
            border: 1.5px dashed #94A3B8; border-radius: 9px; padding: 10px;
            display: flex; gap: 10px; align-items: center; break-inside: avoid;
            background: #fff;
        }
        .label img { width: 74px; height: 74px; flex-shrink: 0; }
        .label-body { min-width: 0; }
        .label-org {
            font-size: .58rem; font-weight: 800; color: #1E3A5F;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .label-name { font-size: .8rem; font-weight: 800; margin: 2px 0; line-height: 1.25; }
        .label-code {
            font-family: ui-monospace, Menlo, monospace; font-size: .74rem; font-weight: 800;
            color: #1D4ED8; background: #EFF6FF; padding: 1px 5px; border-radius: 4px;
            display: inline-block;
        }
        .label-meta { font-size: .62rem; color: #475569; margin-top: 3px; line-height: 1.4; }

        .sheet-foot {
            margin-top: 16px; padding-top: 11px; border-top: 1px solid #E2E8F0;
            display: flex; justify-content: space-between; font-size: .72rem; color: #64748B;
            flex-wrap: wrap; gap: 8px;
        }
        .empty { padding: 50px 20px; text-align: center; color: #94A3B8; font-size: .9rem; }

        @media (max-width: 700px) {
            .labels { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 460px) {
            .labels { grid-template-columns: 1fr; }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; max-width: none; padding: 0; }
            .labels { grid-template-columns: repeat({{ $kolom }}, 1fr); gap: 6px; }
            .label { border-color: #64748B; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div>
        <h1>Label QR Code — {{ $location->nama ?? 'Ruangan' }}</h1>
        <p>{{ $assets->count() }} label siap dicetak · {{ $kolom }} label per baris · Dicetak {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ isset($location) ? route('ruangan.show', $location->slug) : route('dashboard') }}" class="btn btn-back" onclick="if(window.history.length > 1 && document.referrer){ history.back(); return false; }">← Kembali</a>
        <button type="button" class="btn btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>
</div>

<div class="sheet">
    <div class="sheet-head">
        <h2>Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror</h2>
        <p>
            Label Inventaris Sarana &amp; Prasarana ·
            {{ $location->unit->nama ?? 'Unit Sekolah' }} · {{ $location->nama ?? '-' }}
        </p>
    </div>

    @if($assets->isEmpty())
        <div class="empty">Tidak ada barang yang dipilih untuk dicetak.</div>
    @else
        <div class="labels">
            @foreach($assets as $asset)
                <div class="label">
                    <img src="{{ $asset->qrUrl() }}" alt="QR {{ $asset->kode_barang }}">
                    <div class="label-body">
                        <div class="label-org">Inventaris Husnul Abror</div>
                        <div class="label-name">{{ $asset->nama_barang }}</div>
                        <span class="label-code">{{ $asset->kode_barang }}</span>
                        <div class="label-meta">
                            {{ $asset->category->nama ?? 'Umum' }} · {{ $asset->merek->nama ?? 'Tanpa Merek' }}<br>
                            {{ $location->nama ?? '-' }} · {{ $asset->total_qty }} unit
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="sheet-foot">
        <span>Total {{ $assets->count() }} label · {{ $assets->sum('total_qty') }} unit barang</span>
        <span>Scan QR untuk melihat detail & melaporkan kerusakan barang</span>
    </div>
</div>

</body>
</html>
