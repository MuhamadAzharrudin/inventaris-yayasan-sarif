<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RuanganController extends Controller
{
    use HandlesUnitScope;

    /**
     * Halaman "Daftar Barang" pada satu ruangan / kelas / lab.
     */
    public function show(Request $request, string $slug)
    {
        $location = $this->resolveLocation($slug);
        $this->guardUnit($location->unit_id);

        $keyword = trim((string) $request->query('q', ''));

        $query = Asset::with(['category', 'merek', 'location'])
            ->where('location_id', $location->id)
            ->orderBy('nama_barang');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('nama_barang', 'like', "%{$keyword}%")
                    ->orWhere('kode_barang', 'like', "%{$keyword}%")
                    ->orWhere('spesifikasi', 'like', "%{$keyword}%");
            });
        }

        $assets = $query->get();

        // Ringkasan dihitung dari seluruh barang di ruangan (bukan hasil pencarian).
        $semua = Asset::where('location_id', $location->id)->get();

        return view('ruangan.show', [
            'location'         => $location->load('unit'),
            'assets'           => $assets,
            'keyword'          => $keyword,
            'totalItems'       => (int) $semua->sum('total_qty'),
            'totalJenis'       => $semua->count(),
            'totalBaik'        => (int) $semua->sum('kondisi_baik'),
            'totalRusakRingan' => (int) $semua->sum('kondisi_rusak_ringan'),
            'totalRusakBerat'  => (int) $semua->sum('kondisi_rusak_berat'),
            'slug'             => $location->slug,
        ]);
    }

    /**
     * Perbarui rincian kondisi barang.
     *
     * ATURAN VALIDASI:
     *  - Ketiga kolom kondisi wajib angka >= 0.
     *  - Total (baik + rusak ringan + rusak berat) minimal 1 unit.
     *  - Jumlah kondisi baik tidak boleh lebih kecil dari unit yang sedang
     *    dipinjamkan, karena unit tersebut secara fisik masih tercatat baik.
     *  - total_qty selalu dihitung ulang dari ketiga kolom kondisi.
     */
    public function updateKondisi(Request $request, int $id)
    {
        $validated = $request->validate([
            'kondisi_baik'         => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi_rusak_ringan' => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi_rusak_berat'  => ['required', 'integer', 'min:0', 'max:100000'],
        ], [], [
            'kondisi_baik'         => 'jumlah kondisi baik',
            'kondisi_rusak_ringan' => 'jumlah rusak ringan',
            'kondisi_rusak_berat'  => 'jumlah rusak berat',
        ]);

        $asset = Asset::with('location')->findOrFail($id);
        $this->guardUnit($asset->unit_id);

        $baik   = (int) $validated['kondisi_baik'];
        $ringan = (int) $validated['kondisi_rusak_ringan'];
        $berat  = (int) $validated['kondisi_rusak_berat'];

        if (($baik + $ringan + $berat) < 1) {
            return back()->withErrors([
                'kondisi_baik' => 'Total unit barang minimal 1. Gunakan menu hapus barang bila barang sudah tidak ada.',
            ])->withInput();
        }

        $dipinjam = $asset->qtyDipinjam();
        if ($baik < $dipinjam) {
            return back()->withErrors([
                'kondisi_baik' => 'Jumlah kondisi baik tidak boleh kurang dari ' . $dipinjam
                    . ' unit karena unit tersebut sedang dipinjamkan. Catat pengembalian barang terlebih dahulu.',
            ])->withInput();
        }

        $asset->kondisi_baik         = $baik;
        $asset->kondisi_rusak_ringan = $ringan;
        $asset->kondisi_rusak_berat  = $berat;
        $asset->syncTotalQty();
        $asset->save();

        return redirect()->back()->with(
            'success',
            'Status kondisi "' . $asset->nama_barang . '" diperbarui menjadi total '
            . $asset->total_qty . ' unit (baik ' . $baik . ', rusak ringan ' . $ringan
            . ', rusak berat ' . $berat . ').'
        );
    }

    /**
     * Cari lokasi pada cakupan unit pengguna; buat otomatis bila belum ada
     * (khusus Admin Unit) agar tautan ruangan pada sidebar tidak pernah mati.
     */
    private function resolveLocation(string $slug): Location
    {
        $query = Location::query();
        $this->scopeUnit($query);

        $location = (clone $query)->where('slug', $slug)->first();
        if ($location) {
            return $location;
        }

        $foreign = Location::where('slug', $slug)->first();
        if ($foreign) {
            $this->guardUnit($foreign->unit_id);

            return $foreign;
        }

        if ($this->isSuperAdmin()) {
            abort(404, 'Ruangan "' . $slug . '" tidak ditemukan.');
        }

        return Location::create([
            'unit_id' => $this->currentUnitId(),
            'nama'    => Str::title(str_replace('-', ' ', $slug)),
            'slug'    => $slug,
            'tipe'    => match (true) {
                Str::contains($slug, 'perpus')                   => 'perpustakaan',
                Str::contains($slug, ['lab', 'bengkel'])         => 'lab',
                Str::contains($slug, ['guru', 'aula', 'kantor']) => 'kantor',
                Str::contains($slug, 'uks')                      => 'uks',
                default                                          => 'kelas',
            },
        ]);
    }
}
