<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Unit;

/**
 * Halaman publik (landing page) Yayasan.
 */
class LandingController extends Controller
{
    public function index()
    {
        $units  = Unit::orderBy('id')->get();
        $assets = Asset::all(['unit_id', 'total_qty', 'kondisi_baik', 'nilai_estimasi']);

        $unitStats = $units->map(function (Unit $unit) use ($assets) {
            $milik = $assets->where('unit_id', $unit->id);

            return [
                'kode'      => $unit->label(),
                'nama'      => $unit->nama,
                'jenjang'   => $unit->jenjang(),
                'icon'      => $unit->icon(),
                'total_qty' => (int) $milik->sum('total_qty'),
                'jenis'     => $milik->count(),
                'ruangan'   => Location::where('unit_id', $unit->id)->count(),
            ];
        });

        return view('welcome', [
            'unitStats' => $unitStats,
            'ringkasan' => [
                'total_aset'   => (int) $assets->sum('total_qty'),
                'total_jenis'  => $assets->count(),
                'total_unit'   => $units->count(),
                'total_ruang'  => Location::count(),
                'total_kategori' => Category::count(),
                'persen_baik'  => $assets->sum('total_qty') > 0
                    ? round($assets->sum('kondisi_baik') / $assets->sum('total_qty') * 100, 1)
                    : 0.0,
            ],
        ]);
    }
}
