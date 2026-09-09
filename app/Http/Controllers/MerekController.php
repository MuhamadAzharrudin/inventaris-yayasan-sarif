<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\Merek;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Master Data: Merek / Brand (dikelola Admin Unit Sekolah).
 */
class MerekController extends Controller
{
    use HandlesUnitScope;

    public function index()
    {
        $unitId = $this->currentUnitId();

        $mereks = Merek::with('unit')
            ->when($unitId, fn ($q) => $q->where(function ($w) use ($unitId) {
                $w->whereNull('unit_id')->orWhere('unit_id', $unitId);
            }))
            ->orderBy('nama')
            ->get();

        $jumlahAset = Asset::query()
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->selectRaw('merek_id, COUNT(*) as total, SUM(total_qty) as qty')
            ->groupBy('merek_id')
            ->get()
            ->keyBy('merek_id');

        return view('merek.index', compact('mereks', 'jumlahAset'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'      => ['required', 'string', 'max:255', Rule::unique('mereks', 'nama')],
            'kode'      => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.unique' => 'Merek dengan nama tersebut sudah ada.',
        ], [
            'nama' => 'nama merek',
        ]);

        $merek = Merek::create([
            'unit_id'   => $this->currentUnitId(),
            'nama'      => $validated['nama'],
            'kode'      => strtoupper($validated['kode'] ?? Str::substr(Str::slug($validated['nama'], ''), 0, 4)),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        // Dipakai tombol "tambah merek" pada form Tambah/Ubah Barang
        // sehingga isian form tidak hilang.
        if ($request->expectsJson()) {
            return response()->json([
                'ok'    => true,
                'pesan' => 'Merek "' . $merek->nama . '" berhasil ditambahkan.',
                'merek' => [
                    'id'   => $merek->id,
                    'nama' => $merek->nama,
                    'kode' => $merek->kode,
                ],
            ], 201);
        }

        return redirect()->route('merek.index')
            ->with('success', 'Merek "' . $merek->nama . '" berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $merek = Merek::findOrFail($id);
        $this->guardUnit($merek->unit_id);

        $validated = $request->validate([
            'nama'      => ['required', 'string', 'max:255', Rule::unique('mereks', 'nama')->ignore($merek->id)],
            'kode'      => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.unique' => 'Merek dengan nama tersebut sudah ada.',
        ]);

        $merek->update([
            'nama'      => $validated['nama'],
            'kode'      => strtoupper($validated['kode'] ?? $merek->kode),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()->route('merek.index')->with('success', 'Data merek berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $merek = Merek::findOrFail($id);
        $this->guardUnit($merek->unit_id);

        $terpakai = Asset::where('merek_id', $merek->id)->count();
        if ($terpakai > 0) {
            return back()->with('error', 'Merek "' . $merek->nama . '" tidak dapat dihapus karena '
                . 'masih dipakai oleh ' . $terpakai . ' data barang.');
        }

        $nama = $merek->nama;
        $merek->delete();

        return back()->with('success', 'Merek "' . $nama . '" berhasil dihapus.');
    }
}
