<?php

namespace App\Http\Controllers;

use App\Helpers\QrCodeHelper;
use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\AssetMutation;
use App\Models\Category;
use App\Models\Location;
use App\Models\Merek;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsetController extends Controller
{
    use HandlesUnitScope;

    /* ── CRUD ───────────────────────────────────────────────── */

    public function create(string $slug)
    {
        $location = $this->findLocation($slug);

        $locations  = $this->locationQuery()->orderBy('nama')->get();
        $categories = Category::orderBy('nama')->get();
        $mereks     = Merek::orderBy('nama')->get();

        return view('aset.create', compact('location', 'categories', 'mereks', 'locations', 'slug'));
    }

    /**
     * Simpan barang baru + generate QR + catat mutasi masuk.
     *
     * ATURAN KONDISI: total_qty selalu sama dengan
     * kondisi_baik + kondisi_rusak_ringan + kondisi_rusak_berat.
     */
    public function store(Request $request)
    {
        $user = $this->currentUser();

        $validated = $request->validate([
            'nama_barang'          => ['required', 'string', 'max:255'],
            'location_id'          => ['required', 'exists:locations,id'],
            'category_id'          => ['nullable', 'exists:categories,id'],
            'merek_id'             => ['nullable', 'exists:mereks,id'],
            'kode_barang'          => ['nullable', 'string', 'max:60', 'unique:assets,kode_barang'],
            'total_qty'            => ['nullable', 'integer', 'min:1', 'max:100000'],
            'kondisi_baik'         => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi_rusak_ringan' => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi_rusak_berat'  => ['required', 'integer', 'min:0', 'max:100000'],
            'foto_barang'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'spesifikasi'          => ['nullable', 'string', 'max:2000'],
            'nilai_estimasi'       => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ], [
            'foto_barang.image' => 'Berkas foto barang harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto_barang.max'   => 'Ukuran foto barang maksimal 5 MB.',
            'kode_barang.unique' => 'Kode barang tersebut sudah digunakan oleh aset lain.',
        ], [
            'nama_barang'          => 'nama barang',
            'location_id'          => 'lokasi ruangan',
            'kondisi_baik'         => 'jumlah kondisi baik',
            'kondisi_rusak_ringan' => 'jumlah rusak ringan',
            'kondisi_rusak_berat'  => 'jumlah rusak berat',
        ]);

        /** @var Location $location */
        $location = Location::findOrFail($validated['location_id']);
        $this->guardUnit($location->unit_id);

        $baik   = (int) $validated['kondisi_baik'];
        $ringan = (int) $validated['kondisi_rusak_ringan'];
        $berat  = (int) $validated['kondisi_rusak_berat'];
        $total  = $baik + $ringan + $berat;

        if ($total < 1) {
            return back()->withInput()->withErrors([
                'kondisi_baik' => 'Total unit barang minimal 1. Isi rincian kondisi barang terlebih dahulu.',
            ]);
        }

        if ($request->filled('total_qty') && (int) $request->input('total_qty') !== $total) {
            return back()->withInput()->withErrors([
                'total_qty' => 'Total unit (' . (int) $request->input('total_qty') . ') tidak sama dengan '
                    . 'jumlah rincian kondisi (' . $total . ' unit). Perbaiki rincian kondisi barang.',
            ]);
        }

        $unitId      = $location->unit_id ?? $user->unit_id;
        $kodeBarang  = ($validated['kode_barang'] ?? null) ?: $this->generateKodeBarang($location);
        $fotoPath    = $this->storeFoto($request);

        $asset = DB::transaction(function () use (
            $unitId, $location, $validated, $kodeBarang, $fotoPath, $baik, $ringan, $berat, $total, $user
        ) {
            $asset = Asset::create([
                'unit_id'              => $unitId,
                'location_id'          => $location->id,
                'category_id'          => $validated['category_id'] ?? null,
                'merek_id'             => $validated['merek_id'] ?? null,
                'kode_barang'          => $kodeBarang,
                'nama_barang'          => $validated['nama_barang'],
                'total_qty'            => $total,
                'kondisi_baik'         => $baik,
                'kondisi_rusak_ringan' => $ringan,
                'kondisi_rusak_berat'  => $berat,
                'foto_barang'          => $fotoPath,
                'qr_code_path'         => QrCodeHelper::generateSvg($kodeBarang),
                'spesifikasi'          => $validated['spesifikasi'] ?? null,
                'nilai_estimasi'       => $validated['nilai_estimasi'] ?? 0,
            ]);

            AssetMutation::create([
                'asset_id'       => $asset->id,
                'unit_id'        => $unitId,
                'location_id'    => $location->id,
                'tipe'           => 'masuk',
                'jenis'          => 'pengadaan',
                'kondisi_sumber' => 'baik',
                'qty'            => $total,
                'tanggal_keluar' => now()->toDateString(),
                'keterangan'     => 'Pendataan barang baru pada ' . $location->nama,
                'user_id'        => $user->id,
            ]);

            return $asset;
        });

        NotificationService::barangMasuk($asset->fresh(['unit', 'location']), $total, $user);

        return redirect()->route('ruangan.show', ['ruangan' => $location->slug])
            ->with('success', 'Barang "' . $asset->nama_barang . '" berhasil ditambahkan & QR Code otomatis dibuat.');
    }

    public function edit(int $id)
    {
        $asset = Asset::with(['location', 'category', 'merek'])->findOrFail($id);
        $this->guardUnit($asset->unit_id);

        $locations  = $this->locationQuery()->orderBy('nama')->get();
        $categories = Category::orderBy('nama')->get();
        $mereks     = Merek::orderBy('nama')->get();

        return view('aset.edit', compact('asset', 'categories', 'mereks', 'locations'));
    }

    public function update(Request $request, int $id)
    {
        $asset = Asset::with('location')->findOrFail($id);
        $this->guardUnit($asset->unit_id);

        $validated = $request->validate([
            'nama_barang'          => ['required', 'string', 'max:255'],
            'location_id'          => ['required', 'exists:locations,id'],
            'category_id'          => ['nullable', 'exists:categories,id'],
            'merek_id'             => ['nullable', 'exists:mereks,id'],
            'kode_barang'          => ['required', 'string', 'max:60', 'unique:assets,kode_barang,' . $asset->id],
            'kondisi_baik'         => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi_rusak_ringan' => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi_rusak_berat'  => ['required', 'integer', 'min:0', 'max:100000'],
            'foto_barang'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'spesifikasi'          => ['nullable', 'string', 'max:2000'],
            'nilai_estimasi'       => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ], [
            'kode_barang.unique' => 'Kode barang tersebut sudah digunakan oleh aset lain.',
        ]);

        /** @var Location $location */
        $location = Location::findOrFail($validated['location_id']);
        $this->guardUnit($location->unit_id);

        $baik   = (int) $validated['kondisi_baik'];
        $ringan = (int) $validated['kondisi_rusak_ringan'];
        $berat  = (int) $validated['kondisi_rusak_berat'];
        $total  = $baik + $ringan + $berat;

        if ($total < 1) {
            return back()->withInput()->withErrors([
                'kondisi_baik' => 'Total unit barang minimal 1 unit.',
            ]);
        }

        // Unit yang sedang dipinjam tidak boleh "hilang" akibat pengurangan stok baik.
        $dipinjam = $asset->qtyDipinjam();
        if ($baik < $dipinjam) {
            return back()->withInput()->withErrors([
                'kondisi_baik' => 'Jumlah kondisi baik tidak boleh kurang dari ' . $dipinjam
                    . ' unit karena sejumlah unit tersebut sedang dipinjamkan.',
            ]);
        }

        $data = [
            'location_id'          => $location->id,
            'unit_id'              => $location->unit_id ?? $asset->unit_id,
            'category_id'          => $validated['category_id'] ?? null,
            'merek_id'             => $validated['merek_id'] ?? null,
            'kode_barang'          => $validated['kode_barang'],
            'nama_barang'          => $validated['nama_barang'],
            'total_qty'            => $total,
            'kondisi_baik'         => $baik,
            'kondisi_rusak_ringan' => $ringan,
            'kondisi_rusak_berat'  => $berat,
            'spesifikasi'          => $validated['spesifikasi'] ?? null,
            'nilai_estimasi'       => $validated['nilai_estimasi'] ?? 0,
            'qr_code_path'         => QrCodeHelper::generateSvg($validated['kode_barang']),
        ];

        if ($request->hasFile('foto_barang')) {
            $data['foto_barang'] = $this->storeFoto($request);
        }

        $asset->update($data);

        return redirect()->route('ruangan.show', ['ruangan' => $location->slug])
            ->with('success', 'Data barang berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $asset = Asset::with('location')->findOrFail($id);
        $this->guardUnit($asset->unit_id);

        if ($asset->qtyDipinjam() > 0) {
            return back()->with('error', 'Barang tidak dapat dihapus karena masih ada '
                . $asset->qtyDipinjam() . ' unit yang sedang dipinjamkan.');
        }

        $slug = $asset->location->slug ?? null;
        $nama = $asset->nama_barang;
        $asset->delete();

        if ($slug) {
            return redirect()->route('ruangan.show', ['ruangan' => $slug])
                ->with('success', 'Barang "' . $nama . '" berhasil dihapus.');
        }

        return redirect()->route('dashboard')->with('success', 'Barang "' . $nama . '" berhasil dihapus.');
    }

    /* ── SCAN QR CODE (MASTER DATA) ─────────────────────────── */

    /**
     * Halaman scanner QR Code memakai kamera HP / laptop.
     */
    public function scan()
    {
        $query = Asset::with(['location', 'category', 'merek'])->orderBy('nama_barang');
        $this->scopeUnit($query);

        return view('aset.scan', [
            'assets'    => $query->get(),
            'locations' => $this->locationQuery()->orderBy('nama')->get(),
        ]);
    }

    /**
     * Pencarian aset berdasarkan hasil pembacaan QR / barcode (JSON).
     */
    public function lookup(Request $request): JsonResponse
    {
        $kode = trim((string) $request->query('kode', ''));

        if ($kode === '') {
            return response()->json([
                'found'   => false,
                'message' => 'Kode barang kosong. Arahkan kamera ke label QR Code barang.',
            ], 422);
        }

        // QR bisa berisi URL/teks tambahan, ambil token yang menyerupai kode barang.
        $kodeBersih = $this->normalizeScannedCode($kode);

        $query = Asset::with(['location', 'category', 'merek', 'unit']);
        $this->scopeUnit($query);

        $asset = (clone $query)->where('kode_barang', $kodeBersih)->first()
            ?? (clone $query)->where('kode_barang', $kode)->first()
            ?? (clone $query)->where('kode_barang', 'like', '%' . $kodeBersih . '%')->first();

        if (! $asset) {
            return response()->json([
                'found'   => false,
                'kode'    => $kodeBersih,
                'message' => 'Barang dengan kode "' . $kodeBersih . '" tidak ditemukan pada data unit Anda.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'asset' => [
                'id'             => $asset->id,
                'kode_barang'    => $asset->kode_barang,
                'nama_barang'    => $asset->nama_barang,
                'kategori'       => $asset->category->nama ?? 'Umum',
                'merek'          => $asset->merek->nama ?? 'Tanpa Merek',
                'lokasi'         => $asset->location->nama ?? '-',
                'lokasi_slug'    => $asset->location->slug ?? null,
                'unit'           => $asset->unit?->label() ?? '-',
                'total_qty'      => (int) $asset->total_qty,
                'baik'           => (int) $asset->kondisi_baik,
                'rusak_ringan'   => (int) $asset->kondisi_rusak_ringan,
                'rusak_berat'    => (int) $asset->kondisi_rusak_berat,
                'dipinjam'       => $asset->qtyDipinjam(),
                'spesifikasi'    => $asset->spesifikasi,
                'nilai_estimasi' => 'Rp ' . number_format((float) $asset->nilai_estimasi, 0, ',', '.'),
                'foto'           => $asset->fotoUrl(),
                'qr'             => $asset->qrUrl(),
                'updated_at'     => $asset->updated_at?->format('d/m/Y H:i'),
                'url_ruangan'    => $asset->location
                    ? route('ruangan.show', ['ruangan' => $asset->location->slug])
                    : null,
                'url_edit'       => route('aset.edit', $asset->id),
                'url_laporan'    => route('laporan.create', ['asset' => $asset->id]),
            ],
        ]);
    }

    /**
     * Membersihkan hasil scan agar cocok dengan kolom kode_barang.
     */
    private function normalizeScannedCode(string $raw): string
    {
        $raw = trim($raw);

        // Bila QR berisi URL, ambil segmen terakhir / query "kode".
        if (Str::startsWith(Str::lower($raw), ['http://', 'https://'])) {
            $parts = parse_url($raw);
            if (! empty($parts['query'])) {
                parse_str($parts['query'], $params);
                foreach (['kode', 'data', 'code'] as $key) {
                    if (! empty($params[$key])) {
                        return trim((string) $params[$key]);
                    }
                }
            }
            if (! empty($parts['path'])) {
                $segments = array_values(array_filter(explode('/', $parts['path'])));
                if (! empty($segments)) {
                    return urldecode((string) end($segments));
                }
            }
        }

        // Ambil token yang paling menyerupai kode barang (huruf/angka/strip).
        if (preg_match_all('/[A-Za-z0-9\-]{4,}/', $raw, $matches) && ! empty($matches[0])) {
            usort($matches[0], fn ($a, $b) => strlen($b) <=> strlen($a));

            return strtoupper($matches[0][0]);
        }

        return strtoupper($raw);
    }

    /* ── CETAK LABEL QR (MASTER DATA) ───────────────────────── */

    /**
     * Form pemilihan ruangan & barang yang labelnya akan dicetak.
     */
    public function cetakLabel(Request $request)
    {
        $locations = $this->locationQuery()->withCount('assets')->orderBy('nama')->get();

        $selectedSlug = (string) $request->query('ruangan', '');
        $selected     = $locations->firstWhere('slug', $selectedSlug);

        $assets = collect();
        if ($selected) {
            $assets = Asset::with(['category', 'merek'])
                ->where('location_id', $selected->id)
                ->orderBy('nama_barang')
                ->get();
        }

        return view('aset.cetak-label', [
            'locations' => $locations,
            'selected'  => $selected,
            'assets'    => $assets,
        ]);
    }

    /**
     * Pratinjau & cetak label QR untuk barang yang dipilih.
     */
    public function cetakLabelPreview(Request $request)
    {
        $validated = $request->validate([
            'ruangan'    => ['required', 'string'],
            'aset'       => ['required', 'array', 'min:1'],
            'aset.*'     => ['integer', 'exists:assets,id'],
            'per_baris'  => ['nullable', 'integer', 'in:2,3,4'],
        ], [
            'aset.required' => 'Pilih minimal satu barang yang akan dicetak label QR-nya.',
            'aset.min'      => 'Pilih minimal satu barang yang akan dicetak label QR-nya.',
        ]);

        $location = $this->findLocation($validated['ruangan'], false);

        $assets = Asset::with(['category', 'merek', 'location'])
            ->whereIn('id', $validated['aset'])
            ->where('location_id', $location->id)
            ->orderBy('nama_barang')
            ->get();

        if ($assets->isEmpty()) {
            return back()->withErrors([
                'aset' => 'Barang yang dipilih tidak ditemukan pada ruangan tersebut.',
            ]);
        }

        foreach ($assets as $asset) {
            $this->guardUnit($asset->unit_id);
        }

        return view('aset.pdf-labels', [
            'location'  => $location,
            'assets'    => $assets,
            'perBaris'  => (int) ($validated['per_baris'] ?? 3),
        ]);
    }

    /**
     * Cetak label QR seluruh barang pada satu ruangan (dari halaman ruangan).
     */
    public function cetakQr(string $slug)
    {
        $location = $this->findLocation($slug, false);

        $assets = Asset::with(['category', 'merek', 'location'])
            ->where('location_id', $location->id)
            ->orderBy('nama_barang')
            ->get();

        return view('aset.cetak-qr', compact('location', 'assets', 'slug'));
    }

    /**
     * Halaman label siap cetak (HTML -> PDF via peramban).
     */
    public function exportPdf(string $slug)
    {
        $location = $this->findLocation($slug, false);

        $assets = Asset::with(['category', 'merek', 'location'])
            ->where('location_id', $location->id)
            ->orderBy('nama_barang')
            ->get();

        return view('aset.pdf-labels', [
            'location' => $location,
            'assets'   => $assets,
            'perBaris' => 3,
        ]);
    }

    /**
     * Export daftar aset satu ruangan ke CSV/Excel.
     */
    public function exportExcel(string $slug)
    {
        $location = $this->findLocation($slug, false);

        $assets = Asset::with(['category', 'merek', 'location'])
            ->where('location_id', $location->id)
            ->orderBy('nama_barang')
            ->get();

        $filename = 'Data_Aset_' . Str::slug($location->nama) . '_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($assets, $location) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Merek', 'Lokasi',
                'Total Unit', 'Kondisi Baik', 'Rusak Ringan', 'Rusak Berat',
                'Sedang Dipinjam', 'Nilai Estimasi', 'Tautan QR Code',
            ]);

            foreach ($assets as $i => $asset) {
                fputcsv($out, [
                    $i + 1,
                    $asset->kode_barang,
                    $asset->nama_barang,
                    $asset->category->nama ?? '-',
                    $asset->merek->nama ?? '-',
                    $location->nama,
                    $asset->total_qty,
                    $asset->kondisi_baik,
                    $asset->kondisi_rusak_ringan,
                    $asset->kondisi_rusak_berat,
                    $asset->qtyDipinjam(),
                    number_format((float) $asset->nilai_estimasi, 0, ',', '.'),
                    $asset->qrUrl(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /* ── Helper ─────────────────────────────────────────────── */

    /**
     * Query lokasi sesuai cakupan unit pengguna.
     */
    private function locationQuery()
    {
        $query = Location::with('unit');
        $this->scopeUnit($query);

        return $query;
    }

    /**
     * Ambil lokasi berdasarkan slug pada cakupan unit pengguna.
     */
    private function findLocation(string $slug, bool $autoCreate = true): Location
    {
        $location = (clone $this->locationQuery())->where('slug', $slug)->first();

        if ($location) {
            $this->guardUnit($location->unit_id);

            return $location;
        }

        // Slug tidak dikenal di unit ini: cek apakah milik unit lain.
        $foreign = Location::where('slug', $slug)->first();
        if ($foreign) {
            $this->guardUnit($foreign->unit_id);

            return $foreign;
        }

        if (! $autoCreate || $this->isSuperAdmin()) {
            abort(404, 'Ruangan "' . $slug . '" tidak ditemukan.');
        }

        return Location::create([
            'unit_id' => $this->currentUnitId(),
            'nama'    => Str::title(str_replace('-', ' ', $slug)),
            'slug'    => $slug,
            'tipe'    => $this->guessTipe($slug),
        ]);
    }

    private function guessTipe(string $slug): string
    {
        return match (true) {
            Str::contains($slug, 'perpus')                  => 'perpustakaan',
            Str::contains($slug, ['lab', 'bengkel'])        => 'lab',
            Str::contains($slug, ['guru', 'aula', 'kantor']) => 'kantor',
            Str::contains($slug, 'uks')                     => 'uks',
            default                                         => 'kelas',
        };
    }

    /**
     * Kode barang otomatis: {UNIT}-{RUANG}-{ACAK}.
     */
    private function generateKodeBarang(Location $location): string
    {
        $unitKode = strtoupper($location->unit->kode ?? 'UNT');
        if ($unitKode === 'SMP') {
            $unitKode = 'MTS';
        }

        $ruang = collect(explode('-', $location->slug))
            ->reject(fn ($p) => in_array(strtolower($p), ['mi', 'mts', 'smp', 'smk'], true))
            ->map(fn ($p) => strtoupper(Str::substr($p, 0, 3)))
            ->take(2)
            ->implode('');

        $ruang = $ruang !== '' ? $ruang : 'RNG';

        do {
            $kode = $unitKode . '-' . $ruang . '-' . strtoupper(Str::random(4));
        } while (Asset::where('kode_barang', $kode)->exists());

        return $kode;
    }

    /**
     * Simpan foto barang ke public/uploads/assets.
     */
    private function storeFoto(Request $request): ?string
    {
        if (! $request->hasFile('foto_barang')) {
            return null;
        }

        $file      = $request->file('foto_barang');
        $filename  = time() . '_' . Str::random(8) . '.' . strtolower($file->getClientOriginalExtension());
        $directory = public_path('uploads/assets');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file->move($directory, $filename);

        return asset('uploads/assets/' . $filename);
    }
}
