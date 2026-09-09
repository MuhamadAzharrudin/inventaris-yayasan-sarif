<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Report;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Menu "Unit Sekolah" pada sisi Admin Yayasan.
 *
 * Admin Yayasan meninjau DETAIL INVENTARIS tiap unit sekolah dari
 * konteks Yayasan (tidak berpindah / masuk ke dashboard sekolah).
 */
class UnitInventoryController extends Controller
{
    /**
     * Ringkasan seluruh unit sekolah + rekap jumlah aset.
     */
    public function index()
    {
        $units  = Unit::orderBy('id')->get();
        $assets = Asset::all();

        $rows = $units->map(function (Unit $unit) use ($assets) {
            $milik = $assets->where('unit_id', $unit->id);

            return [
                'unit'         => $unit,
                'jenis'        => $milik->count(),
                'total_qty'    => (int) $milik->sum('total_qty'),
                'baik'         => (int) $milik->sum('kondisi_baik'),
                'rusak_ringan' => (int) $milik->sum('kondisi_rusak_ringan'),
                'rusak_berat'  => (int) $milik->sum('kondisi_rusak_berat'),
                'nilai'        => (float) $milik->sum('nilai_estimasi'),
                'ruangan'      => Location::where('unit_id', $unit->id)->count(),
                'laporan'      => Report::where('unit_id', $unit->id)->whereIn('status', ['pending', 'proses'])->count(),
            ];
        });

        return view('yayasan.unit-index', [
            'rows'  => $rows,
            'total' => [
                'jenis'     => $assets->count(),
                'total_qty' => (int) $assets->sum('total_qty'),
                'nilai'     => (float) $assets->sum('nilai_estimasi'),
                'ruangan'   => Location::count(),
            ],
        ]);
    }

    /**
     * Detail inventaris satu unit sekolah (read-only bagi Yayasan).
     */
    public function show(Request $request, int $unitId)
    {
        $unit = Unit::findOrFail($unitId);

        $keyword    = trim((string) $request->query('q', ''));
        $lokasiId   = $request->query('lokasi');
        $kondisi    = (string) $request->query('kondisi', 'semua');
        $lokasiId   = is_numeric($lokasiId) ? (int) $lokasiId : null;

        if (! in_array($kondisi, ['semua', 'baik', 'rusak_ringan', 'rusak_berat'], true)) {
            $kondisi = 'semua';
        }

        $query = Asset::with(['location', 'category', 'merek'])
            ->where('unit_id', $unit->id)
            ->orderBy('nama_barang');

        if ($keyword !== '') {
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('nama_barang', 'like', "%{$keyword}%")
                    ->orWhere('kode_barang', 'like', "%{$keyword}%")
                    ->orWhere('spesifikasi', 'like', "%{$keyword}%");
            });
        }

        if ($lokasiId) {
            $query->where('location_id', $lokasiId);
        }

        if ($kondisi === 'baik') {
            $query->where('kondisi_baik', '>', 0);
        } elseif ($kondisi === 'rusak_ringan') {
            $query->where('kondisi_rusak_ringan', '>', 0);
        } elseif ($kondisi === 'rusak_berat') {
            $query->where('kondisi_rusak_berat', '>', 0);
        }

        $assets = $query->paginate(15)->withQueryString();

        // Rekap penuh unit (tidak terpengaruh filter tabel).
        $semua     = Asset::where('unit_id', $unit->id)->get();
        $locations = Location::withCount('assets')->where('unit_id', $unit->id)->orderBy('nama')->get();

        $perRuangan = $locations->map(function (Location $loc) use ($semua) {
            $milik = $semua->where('location_id', $loc->id);

            return [
                'lokasi'       => $loc,
                'jenis'        => $milik->count(),
                'total_qty'    => (int) $milik->sum('total_qty'),
                'baik'         => (int) $milik->sum('kondisi_baik'),
                'rusak_ringan' => (int) $milik->sum('kondisi_rusak_ringan'),
                'rusak_berat'  => (int) $milik->sum('kondisi_rusak_berat'),
                'nilai'        => (float) $milik->sum('nilai_estimasi'),
            ];
        });

        return view('yayasan.unit-show', [
            'unit'       => $unit,
            'assets'     => $assets,
            'locations'  => $locations,
            'perRuangan' => $perRuangan,
            'filters'    => [
                'q'       => $keyword,
                'lokasi'  => $lokasiId,
                'kondisi' => $kondisi,
            ],
            'rekap' => [
                'jenis'        => $semua->count(),
                'total_qty'    => (int) $semua->sum('total_qty'),
                'baik'         => (int) $semua->sum('kondisi_baik'),
                'rusak_ringan' => (int) $semua->sum('kondisi_rusak_ringan'),
                'rusak_berat'  => (int) $semua->sum('kondisi_rusak_berat'),
                'nilai'        => (float) $semua->sum('nilai_estimasi'),
                'ruangan'      => $locations->count(),
                'laporan_aktif' => Report::where('unit_id', $unit->id)
                                    ->whereIn('status', ['pending', 'proses'])->count(),
            ],
        ]);
    }

    /**
     * Unduh rekap inventaris satu unit (CSV).
     */
    public function export(int $unitId)
    {
        $unit = Unit::findOrFail($unitId);

        $assets = Asset::with(['location', 'category', 'merek'])
            ->where('unit_id', $unit->id)
            ->orderBy('nama_barang')
            ->get();

        $filename = 'Inventaris_' . Str::slug($unit->nama) . '_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($assets, $unit) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'No', 'Unit', 'Kode Barang', 'Nama Barang', 'Kategori', 'Merek', 'Ruangan',
                'Total Unit', 'Baik', 'Rusak Ringan', 'Rusak Berat', 'Nilai Estimasi',
            ]);

            foreach ($assets as $i => $a) {
                fputcsv($out, [
                    $i + 1,
                    $unit->nama,
                    $a->kode_barang,
                    $a->nama_barang,
                    $a->category->nama ?? '-',
                    $a->merek->nama ?? '-',
                    $a->location->nama ?? '-',
                    $a->total_qty,
                    $a->kondisi_baik,
                    $a->kondisi_rusak_ringan,
                    $a->kondisi_rusak_berat,
                    number_format((float) $a->nilai_estimasi, 0, ',', '.'),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
