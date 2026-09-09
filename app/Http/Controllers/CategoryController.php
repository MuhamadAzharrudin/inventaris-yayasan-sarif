<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Master Data: Kategori Barang (dikelola Admin Unit Sekolah).
 */
class CategoryController extends Controller
{
    use HandlesUnitScope;

    public function index()
    {
        $unitId = $this->currentUnitId();

        $categories = Category::with('unit')
            ->when($unitId, fn ($q) => $q->where(function ($w) use ($unitId) {
                $w->whereNull('unit_id')->orWhere('unit_id', $unitId);
            }))
            ->orderBy('nama')
            ->get();

        $jumlahAset = Asset::query()
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->selectRaw('category_id, COUNT(*) as total, SUM(total_qty) as qty')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        return view('categories.index', compact('categories', 'jumlahAset'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'      => ['required', 'string', 'max:255', Rule::unique('categories', 'nama')],
            'kode'      => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.unique' => 'Kategori dengan nama tersebut sudah ada.',
        ], [
            'nama' => 'nama kategori',
        ]);

        $category = Category::create([
            'unit_id'   => $this->currentUnitId(),
            'nama'      => $validated['nama'],
            'kode'      => strtoupper($validated['kode'] ?? Str::substr(Str::slug($validated['nama'], ''), 0, 4)),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        // Dipakai tombol "tambah kategori" pada form Tambah/Ubah Barang
        // sehingga isian form tidak hilang.
        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'pesan'    => 'Kategori "' . $category->nama . '" berhasil ditambahkan.',
                'kategori' => [
                    'id'   => $category->id,
                    'nama' => $category->nama,
                    'kode' => $category->kode,
                ],
            ], 201);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Kategori "' . $category->nama . '" berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);
        $this->guardUnit($category->unit_id);

        $validated = $request->validate([
            'nama'      => ['required', 'string', 'max:255', Rule::unique('categories', 'nama')->ignore($category->id)],
            'kode'      => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.unique' => 'Kategori dengan nama tersebut sudah ada.',
        ]);

        $category->update([
            'nama'      => $validated['nama'],
            'kode'      => strtoupper($validated['kode'] ?? $category->kode),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()->route('categories.index')->with('success', 'Data kategori berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $category = Category::findOrFail($id);
        $this->guardUnit($category->unit_id);

        $terpakai = Asset::where('category_id', $category->id)->count();
        if ($terpakai > 0) {
            return back()->with('error', 'Kategori "' . $category->nama . '" tidak dapat dihapus karena '
                . 'masih dipakai oleh ' . $terpakai . ' data barang.');
        }

        $nama = $category->nama;
        $category->delete();

        return back()->with('success', 'Kategori "' . $nama . '" berhasil dihapus.');
    }
}
