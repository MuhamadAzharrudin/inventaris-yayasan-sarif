<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan #{{ $report->id }} — {{ $report->judul }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #F1F5F9; color: #0F172A; padding: 18px; }
        .toolbar {
            max-width: 820px; margin: 0 auto 16px; background: #fff; border: 1px solid #E2E8F0;
            border-radius: 12px; padding: 13px 17px; display: flex; justify-content: space-between;
            align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px; border-radius: 9px;
            border: 1px solid transparent; font-size: .82rem; font-weight: 700; text-decoration: none;
            cursor: pointer; font-family: inherit;
        }
        .btn-print { background: #2563EB; color: #fff; }
        .btn-back { background: #fff; color: #1E3A5F; border-color: #E2E8F0; }

        .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 32px; border: 1px solid #E2E8F0; border-radius: 12px; }
        .head { text-align: center; border-bottom: 3px double #0F172A; padding-bottom: 14px; margin-bottom: 20px; }
        .head h1 { font-size: 1.05rem; font-weight: 800; text-transform: uppercase; letter-spacing: .02em; }
        .head h2 { font-size: .92rem; font-weight: 700; margin-top: 3px; }
        .head p { font-size: .76rem; color: #475569; margin-top: 5px; }
        .doc-title { text-align: center; margin-bottom: 20px; }
        .doc-title h3 { font-size: 1rem; font-weight: 800; text-transform: uppercase; text-decoration: underline; }
        .doc-title p { font-size: .78rem; color: #475569; margin-top: 3px; }

        table.meta { width: 100%; border-collapse: collapse; font-size: .84rem; margin-bottom: 18px; }
        table.meta th, table.meta td { padding: 8px 10px; border: 1px solid #CBD5E1; text-align: left; vertical-align: top; }
        table.meta th { background: #F8FAFC; width: 34%; font-weight: 700; }

        .section-title { font-size: .84rem; font-weight: 800; margin: 16px 0 7px; text-transform: uppercase; letter-spacing: .04em; }
        .box { border: 1px solid #CBD5E1; border-radius: 8px; padding: 12px 14px; font-size: .85rem; line-height: 1.6; }
        .box-green { background: #F0FDF4; border-color: #86EFAC; }
        .box-red { background: #FEF2F2; border-color: #FCA5A5; }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 100px;
            font-size: .72rem; font-weight: 800;
        }
        .b-green { background: #DCFCE7; color: #15803D; }
        .b-amber { background: #FEF3C7; color: #92400E; }
        .b-blue { background: #DBEAFE; color: #1E40AF; }
        .b-red { background: #FEE2E2; color: #B91C1C; }

        .qr-wrap { display: flex; gap: 14px; align-items: center; }
        .qr-wrap img { width: 88px; height: 88px; border: 1px solid #CBD5E1; border-radius: 8px; padding: 3px; background: #fff; }

        .ttd { display: flex; justify-content: space-between; gap: 20px; margin-top: 42px; flex-wrap: wrap; }
        .ttd div { text-align: center; font-size: .82rem; min-width: 190px; }
        .ttd .line { margin-top: 62px; border-top: 1px solid #0F172A; padding-top: 5px; font-weight: 700; }
        .foot { margin-top: 26px; padding-top: 11px; border-top: 1px solid #E2E8F0; font-size: .7rem; color: #64748B; text-align: center; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; max-width: none; padding: 0; }
            @page { margin: 18mm; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div>
        <strong style="font-size:.92rem;">Laporan #{{ $report->id }}</strong>
        <div style="font-size:.78rem;color:#64748B;">{{ $report->jenisLabel() }} · {{ $report->unit->nama ?? '-' }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('laporan.index') }}" class="btn btn-back" onclick="if(window.history.length > 1 && document.referrer){ history.back(); return false; }">← Kembali</a>
        <button type="button" class="btn btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>
</div>

<div class="sheet">
    <div class="head">
        <h1>Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror</h1>
        <h2>{{ $report->unit->nama ?? 'Unit Sekolah' }}</h2>
        <p>Sistem Informasi Manajemen Aset Inventaris Sarana &amp; Prasarana Terintegrasi QR Code</p>
    </div>

    <div class="doc-title">
        <h3>{{ strtoupper($report->jenisLabel()) }}</h3>
        <p>Nomor Dokumen: LAP/{{ str_pad((string) $report->id, 4, '0', STR_PAD_LEFT) }}/{{ strtoupper($report->unit->kode ?? 'YYS') }}/{{ $report->created_at?->format('m/Y') }}</p>
    </div>

    <table class="meta">
        <tr>
            <th>Judul Laporan</th>
            <td><strong>{{ $report->judul }}</strong></td>
        </tr>
        <tr>
            <th>Jenis Laporan</th>
            <td>{{ $report->jenisLabel() }}</td>
        </tr>
        <tr>
            <th>Unit Sekolah</th>
            <td>{{ $report->unit->nama ?? '-' }} (Kepala Unit: {{ $report->unit->kepala_unit ?? '-' }})</td>
        </tr>
        <tr>
            <th>Nama Barang</th>
            <td>{{ $report->asset->nama_barang ?? '-' }}</td>
        </tr>
        <tr>
            <th>Kode Barang</th>
            <td>{{ $report->asset->kode_barang ?? '-' }}</td>
        </tr>
        <tr>
            <th>Kategori / Merek</th>
            <td>{{ $report->asset->category->nama ?? '-' }} / {{ $report->asset->merek->nama ?? '-' }}</td>
        </tr>
        <tr>
            <th>Lokasi Ruangan</th>
            <td>{{ $report->location->nama ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jumlah Unit Terdampak</th>
            <td>{{ $report->qty ? $report->qty . ' unit' : '-' }}</td>
        </tr>
        <tr>
            <th>Pelapor</th>
            <td>{{ $report->user->name ?? '-' }} · {{ $report->created_at?->format('d F Y H:i') }}</td>
        </tr>
        <tr>
            <th>Status Laporan</th>
            <td>
                @if($report->isPending())
                    <span class="badge b-amber">MENUNGGU VERIFIKASI</span>
                @elseif($report->isProses())
                    <span class="badge b-blue">SEDANG DIPROSES YAYASAN</span>
                @elseif($report->isRejected())
                    <span class="badge b-red">SELESAI — DITOLAK</span>
                @else
                    <span class="badge b-green">SELESAI — DISETUJUI</span>
                @endif
            </td>
        </tr>
        @if($report->verified_at)
        <tr>
            <th>Verifikator Yayasan</th>
            <td>{{ $report->verifier->name ?? 'Admin Yayasan' }} · {{ $report->verified_at->format('d F Y H:i') }}</td>
        </tr>
        @endif
    </table>

    @if($report->asset)
        <div class="section-title">Identitas QR Code Barang</div>
        <div class="box qr-wrap">
            <img src="{{ $report->asset->qrUrl() }}" alt="QR {{ $report->asset->kode_barang }}">
            <div>
                <div><strong>{{ $report->asset->nama_barang }}</strong></div>
                <div>Kode: {{ $report->asset->kode_barang }}</div>
                <div>Kondisi tercatat: baik {{ $report->asset->kondisi_baik }} ·
                    rusak ringan {{ $report->asset->kondisi_rusak_ringan }} ·
                    rusak berat {{ $report->asset->kondisi_rusak_berat }}
                    (total {{ $report->asset->total_qty }} unit)</div>
                <div>Spesifikasi: {{ $report->asset->spesifikasi ?? '-' }}</div>
            </div>
        </div>
    @endif

    <div class="section-title">Uraian Laporan</div>
    <div class="box">{{ $report->deskripsi }}</div>

    @if($report->tanggapan)
        <div class="section-title">Tanggapan &amp; Hasil Verifikasi Yayasan</div>
        <div class="box {{ $report->isRejected() ? 'box-red' : 'box-green' }}">{{ $report->tanggapan }}</div>
    @endif

    <div class="ttd">
        <div>
            <div>Pelapor / Admin Unit</div>
            <div class="line">{{ $report->user->name ?? '____________________' }}</div>
        </div>
        <div>
            <div>Mengetahui, Admin Yayasan</div>
            <div class="line">{{ $report->verifier->name ?? '____________________' }}</div>
        </div>
    </div>

    <div class="foot">
        Dokumen ini dicetak dari Sistem Inventaris Husnul Abror pada {{ now()->format('d/m/Y H:i') }} ·
        Keaslian data dapat diverifikasi melalui QR Code barang.
    </div>
</div>

</body>
</html>
