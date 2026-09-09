<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Master Data: Lokasi / Ruangan (dikelola Admin Unit Sekolah).
 *
 * Daftar ruangan di sini menjadi sumber menu "Ruang Kelas & Fasilitas"
 * pada sidebar, sehingga penambahan / penghapusan ruangan langsung
 * tercermin pada navigasi.
 */
class LocationController extends Controller
{
    use HandlesUnitScope;

    /** Tipe ruangan yang tersedia. */
    public const TIPE = ['kelas', 'lab', 'perpustakaan', 'kantor', 'uks', 'bengkel', 'aula', 'lainnya'];

    public function index()
    {
        $query = Location::with('unit')->withCount('assets')->orderBy('nama');
        $this->scopeUnit($query);

        $locations = $query->get();

        $rekap = Asset::query()
            ->when($this->currentUnitId(), fn ($q, $unitId) => $q->where('unit_id', $unitId))
            ->selectRaw('location_id, SUM(total_qty) as qty, SUM(kondisi_rusak_ringan + kondisi_rusak_berat) as rusak')
            ->groupBy('location_id')
            ->get()
            ->keyBy('location_id');

        return view('locations.index', [
            'locations' => $locations,
            'rekap'     => $rekap,
            'tipeList'  => self::TIPE,
        ]);
    }

    public function store(Request $request)
    {
        $unitId = $this->currentUnitId();

        $validated = $request->validate([
            'nama'      => ['required', 'string', 'max:255'],
            'tipe'      => ['required', 'in:' . implode(',', self::TIPE)],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [], [
            'nama' => 'nama ruangan',
            'tipe' => 'tipe ruangan',
        ]);

        // Nama ruangan harus unik pada unit yang sama.
        $duplikat = Location::where('unit_id', $unitId)
            ->whereRaw('LOWER(nama) = ?', [Str::lower($validated['nama'])])
            ->exists();

        if ($duplikat) {
            return back()->withInput()->withErrors([
                'nama' => 'Ruangan dengan nama tersebut sudah terdaftar pada unit Anda.',
            ]);
        }

        Location::create([
            'unit_id'   => $unitId,
            'nama'      => $validated['nama'],
            'slug'      => $this->uniqueSlug($validated['nama'], $unitId),
            'tipe'      => $validated['tipe'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()->route('locations.index')
            ->with('success', 'Ruangan "' . $validated['nama'] . '" berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $location = Location::findOrFail($id);
        $this->guardUnit($location->unit_id);

        $validated = $request->validate([
            'nama'      => ['required', 'string', 'max:255'],
            'tipe'      => ['required', 'in:' . implode(',', self::TIPE)],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ]);

        $duplikat = Location::where('unit_id', $location->unit_id)
            ->where('id', '<>', $location->id)
            ->whereRaw('LOWER(nama) = ?', [Str::lower($validated['nama'])])
            ->exists();

        if ($duplikat) {
            return back()->withInput()->withErrors([
                'nama' => 'Ruangan dengan nama tersebut sudah terdaftar pada unit Anda.',
            ]);
        }

        $location->update([
            'nama'      => $validated['nama'],
            'tipe'      => $validated['tipe'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()->route('locations.index')->with('success', 'Data ruangan berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $location = Location::findOrFail($id);
        $this->guardUnit($location->unit_id);

        $jumlahAset = Asset::where('location_id', $location->id)->count();
        if ($jumlahAset > 0) {
            return back()->with('error', 'Ruangan "' . $location->nama . '" tidak dapat dihapus karena '
                . 'masih memuat ' . $jumlahAset . ' data barang. Pindahkan atau hapus barangnya terlebih dahulu.');
        }

        $nama = $location->nama;
        $location->delete();

        return back()->with('success', 'Ruangan "' . $nama . '" berhasil dihapus.');
    }

    /**
     * Slug unik, diberi prefiks kode unit agar tidak bertabrakan antar unit.
     */
    private function uniqueSlug(string $nama, ?int $unitId): string
    {
        $prefix = '';
        if ($unitId) {
            $kode = strtolower((string) \App\Models\Unit::find($unitId)?->kode);
            if ($kode === 'smp') {
                $kode = 'mts';
            }
            $prefix = $kode !== '' ? $kode . '-' : '';
        }

        $base = $prefix . Str::slug($nama);
        $slug = $base;
        $i    = 2;

        while (Location::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
