<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\AssetMutation;
use App\Models\Report;
use App\Models\Unit;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mutasi aset: Barang Masuk & Barang Keluar.
 *
 * Barang Keluar mendukung dua jenis laporan yang dibuat Admin Unit:
 *  1. "penggantian" — barang rusak yang harus diganti (unit rusak dikeluarkan
 *     dari stok ruangan, lalu diajukan ke Yayasan untuk verifikasi).
 *  2. "peminjaman"  — barang yang dipinjamkan keluar ruangan. Stok kondisi
 *     tidak berubah (barang masih milik ruangan), namun unit yang sedang
 *     dipinjam tidak dapat dipinjamkan lagi sampai dikembalikan.
 *
 * Kontrak data yang selalu dijaga:
 *     total_qty = kondisi_baik + kondisi_rusak_ringan + kondisi_rusak_berat
 */
class MutasiController extends Controller
{
    use HandlesUnitScope;

    /* ── BARANG MASUK ───────────────────────────────────────── */

    public function masuk(Request $request)
    {
        $filters = $this->filters($request, AssetMutation::JENIS_MASUK);

        $query = AssetMutation::with(['asset.category', 'asset.merek', 'location', 'unit', 'user'])
            ->where('tipe', 'masuk')
            ->latest();

        $this->scopeUnit($query);
        $this->applyFilters($query, $filters);

        $mutations = $query->paginate(15)->withQueryString();

        return view('mutasi.masuk', [
            'mutations' => $mutations,
            'assets'    => $this->assetOptions(),
            'filters'   => $filters,
            'units'     => $this->isSuperAdmin() ? Unit::orderBy('id')->get() : collect(),
            'ringkasan' => $this->ringkasan('masuk'),
        ]);
    }

    /**
     * Catat barang masuk (pengadaan baru / barang pengganti diterima).
     *
     * Aturan validasi:
     *  - Barang wajib berada di unit pengguna.
     *  - Qty minimal 1 dan maksimal 10.000 unit per transaksi.
     *  - Unit yang masuk selalu dicatat sebagai kondisi baik sehingga
     *    total_qty ikut bertambah sesuai kontrak kondisi.
     */
    public function storeMasuk(Request $request)
    {
        $user = $this->currentUser();

        $validated = $request->validate([
            'asset_id'       => ['required', 'exists:assets,id'],
            'jenis'          => ['required', 'in:pengadaan,penggantian_baru'],
            'qty'            => ['required', 'integer', 'min:1', 'max:10000'],
            'tanggal_masuk'  => ['required', 'date', 'before_or_equal:today'],
            'keterangan'     => ['nullable', 'string', 'max:500'],
        ], [], [
            'asset_id'      => 'barang',
            'jenis'         => 'jenis barang masuk',
            'qty'           => 'jumlah unit',
            'tanggal_masuk' => 'tanggal masuk',
        ]);

        /** @var Asset $asset */
        $asset = Asset::with('location')->findOrFail($validated['asset_id']);
        $this->guardUnit($asset->unit_id);

        $qty = (int) $validated['qty'];

        DB::transaction(function () use ($asset, $qty, $validated, $user) {
            $asset->kondisi_baik = (int) $asset->kondisi_baik + $qty;
            $asset->syncTotalQty();
            $asset->save();

            AssetMutation::create([
                'asset_id'       => $asset->id,
                'unit_id'        => $asset->unit_id,
                'location_id'    => $asset->location_id,
                'tipe'           => 'masuk',
                'jenis'          => $validated['jenis'],
                'kondisi_sumber' => 'baik',
                'qty'            => $qty,
                'tanggal_keluar' => null,
                'keterangan'     => ($validated['keterangan'] ?? null)
                    ?: ($validated['jenis'] === 'penggantian_baru'
                        ? 'Barang pengganti diterima di ' . ($asset->location->nama ?? 'ruangan')
                        : 'Pengadaan barang baru di ' . ($asset->location->nama ?? 'ruangan')),
                'user_id'        => $user->id,
            ]);
        });

        NotificationService::barangMasuk($asset->fresh(['unit', 'location']), $qty, $user);

        return redirect()->route('laporan.masuk')
            ->with('success', $qty . ' unit ' . $asset->nama_barang . ' berhasil dicatat sebagai barang masuk.');
    }

    /* ── BARANG KELUAR ──────────────────────────────────────── */

    public function keluar(Request $request)
    {
        $filters = $this->filters($request, AssetMutation::JENIS_KELUAR);

        $query = AssetMutation::with(['asset.category', 'asset.merek', 'location', 'unit', 'user', 'report'])
            ->where('tipe', 'keluar')
            ->latest();

        $this->scopeUnit($query);
        $this->applyFilters($query, $filters);

        $mutations = $query->paginate(15)->withQueryString();

        return view('mutasi.keluar', [
            'mutations' => $mutations,
            'assets'    => $this->assetOptions(),
            'filters'   => $filters,
            'units'     => $this->isSuperAdmin() ? Unit::orderBy('id')->get() : collect(),
            'ringkasan' => $this->ringkasanKeluar(),
        ]);
    }

    /**
     * Buat laporan barang keluar.
     *
     * ATURAN VALIDASI STATUS / KONDISI (lengkap, bukan placeholder):
     *
     *  A. jenis = "penggantian" (barang rusak yang harus diganti)
     *     - kondisi_sumber wajib "rusak_ringan" atau "rusak_berat".
     *     - qty <= jumlah unit pada kondisi tersebut (tidak boleh mengeluarkan
     *       unit rusak lebih banyak daripada yang tercatat).
     *     - Efek: kondisi tersebut dikurangi qty, total_qty ikut turun.
     *     - Menghasilkan Report jenis "penggantian" berstatus pending untuk
     *       diverifikasi Admin Yayasan.
     *
     *  B. jenis = "peminjaman" (barang dipinjamkan)
     *     - peminjam wajib diisi.
     *     - tanggal_keluar wajib, tidak boleh di masa depan.
     *     - tanggal_kembali_rencana wajib, minimal sama dengan tanggal_keluar.
     *     - qty <= unit kondisi baik yang belum dipinjam
     *       (kondisi_baik - total qty peminjaman yang masih aktif).
     *     - Efek: kondisi & total_qty TIDAK berubah (barang tetap milik
     *       ruangan), namun unit tersebut terhitung sedang keluar.
     *     - Menghasilkan Report jenis "peminjaman" berstatus pending.
     */
    public function storeKeluar(Request $request)
    {
        $user = $this->currentUser();

        $validated = $request->validate([
            'asset_id'                => ['required', 'exists:assets,id'],
            'jenis'                   => ['required', 'in:penggantian,peminjaman'],
            'qty'                     => ['required', 'integer', 'min:1'],
            'kondisi_sumber'          => ['nullable', 'in:rusak_ringan,rusak_berat'],
            'peminjam'                => ['nullable', 'string', 'max:150'],
            'kontak_peminjam'         => ['nullable', 'string', 'max:60'],
            'tanggal_keluar'          => ['required', 'date', 'before_or_equal:today'],
            'tanggal_kembali_rencana' => ['nullable', 'date', 'after_or_equal:tanggal_keluar'],
            'keterangan'              => ['required', 'string', 'max:1000'],
        ], [
            'tanggal_keluar.before_or_equal'          => 'Tanggal keluar tidak boleh melewati hari ini.',
            'tanggal_kembali_rencana.after_or_equal'  => 'Rencana tanggal kembali tidak boleh lebih awal dari tanggal keluar.',
        ], [
            'asset_id'                => 'barang',
            'jenis'                   => 'jenis barang keluar',
            'qty'                     => 'jumlah unit',
            'kondisi_sumber'          => 'kondisi barang yang dikeluarkan',
            'peminjam'                => 'nama peminjam',
            'kontak_peminjam'         => 'kontak peminjam',
            'tanggal_keluar'          => 'tanggal keluar',
            'tanggal_kembali_rencana' => 'rencana tanggal kembali',
            'keterangan'              => 'keterangan / alasan',
        ]);

        /** @var Asset $asset */
        $asset = Asset::with(['location', 'unit'])->findOrFail($validated['asset_id']);
        $this->guardUnit($asset->unit_id);

        $jenis = $validated['jenis'];
        $qty   = (int) $validated['qty'];

        // ── Validasi khusus per jenis ─────────────────────────
        if ($jenis === 'penggantian') {
            $kondisi = $validated['kondisi_sumber'] ?? null;

            if (! in_array($kondisi, ['rusak_ringan', 'rusak_berat'], true)) {
                return back()->withInput()->withErrors([
                    'kondisi_sumber' => 'Pilih kondisi unit rusak yang dikeluarkan (rusak ringan atau rusak berat).',
                ]);
            }

            $tersedia = $asset->qtyKondisi($kondisi);
            if ($tersedia < 1) {
                return back()->withInput()->withErrors([
                    'kondisi_sumber' => 'Tidak ada unit ' . str_replace('_', ' ', $kondisi)
                        . ' yang tercatat pada barang ini. Perbarui status kondisi barang terlebih dahulu.',
                ]);
            }

            if ($qty > $tersedia) {
                return back()->withInput()->withErrors([
                    'qty' => 'Jumlah melebihi stok. Unit ' . str_replace('_', ' ', $kondisi)
                        . ' yang tersedia hanya ' . $tersedia . ' unit.',
                ]);
            }
        } else { // peminjaman
            if (trim((string) ($validated['peminjam'] ?? '')) === '') {
                return back()->withInput()->withErrors([
                    'peminjam' => 'Nama peminjam wajib diisi untuk laporan peminjaman barang.',
                ]);
            }

            if (empty($validated['tanggal_kembali_rencana'])) {
                return back()->withInput()->withErrors([
                    'tanggal_kembali_rencana' => 'Rencana tanggal kembali wajib diisi untuk peminjaman barang.',
                ]);
            }

            $siapPinjam = $asset->qtySiapPinjam();
            if ($siapPinjam < 1) {
                return back()->withInput()->withErrors([
                    'qty' => 'Tidak ada unit kondisi baik yang tersedia untuk dipinjamkan '
                        . '(kondisi baik: ' . $asset->kondisi_baik . ' unit, sedang dipinjam: '
                        . $asset->qtyDipinjam() . ' unit).',
                ]);
            }

            if ($qty > $siapPinjam) {
                return back()->withInput()->withErrors([
                    'qty' => 'Jumlah melebihi unit yang dapat dipinjamkan. Tersedia ' . $siapPinjam
                        . ' unit kondisi baik (total baik ' . $asset->kondisi_baik . ' unit, '
                        . $asset->qtyDipinjam() . ' unit sedang dipinjam).',
                ]);
            }
        }

        $mutation = DB::transaction(function () use ($asset, $jenis, $qty, $validated, $user) {
            $judul = $jenis === 'penggantian'
                ? 'Pengajuan Penggantian ' . $qty . ' Unit ' . $asset->nama_barang
                : 'Peminjaman ' . $qty . ' Unit ' . $asset->nama_barang;

            $deskripsi = $jenis === 'penggantian'
                ? 'Sebanyak ' . $qty . ' unit ' . $asset->nama_barang . ' (' . $asset->kode_barang . ') '
                    . 'berkondisi ' . str_replace('_', ' ', (string) $validated['kondisi_sumber'])
                    . ' dikeluarkan dari ' . ($asset->location->nama ?? 'ruangan')
                    . ' dan diajukan untuk diganti. Alasan: ' . $validated['keterangan']
                : 'Sebanyak ' . $qty . ' unit ' . $asset->nama_barang . ' (' . $asset->kode_barang . ') '
                    . 'dipinjamkan kepada ' . $validated['peminjam']
                    . ' pada ' . $validated['tanggal_keluar']
                    . ' dan direncanakan kembali ' . $validated['tanggal_kembali_rencana']
                    . '. Keperluan: ' . $validated['keterangan'];

            // Laporan untuk diverifikasi Admin Yayasan.
            $report = Report::create([
                'unit_id'     => $asset->unit_id,
                'asset_id'    => $asset->id,
                'location_id' => $asset->location_id,
                'user_id'     => $user->id,
                'jenis'       => $jenis,
                'judul'       => $judul,
                'deskripsi'   => $deskripsi,
                'qty'         => $qty,
                'status'      => 'pending',
                'verifikasi'  => null,
            ]);

            // Penggantian mengurangi stok kondisi rusak yang bersangkutan.
            if ($jenis === 'penggantian') {
                $kondisi = $validated['kondisi_sumber'];
                $kolom   = $kondisi === 'rusak_ringan' ? 'kondisi_rusak_ringan' : 'kondisi_rusak_berat';

                $asset->{$kolom} = max(0, (int) $asset->{$kolom} - $qty);
                $asset->syncTotalQty();
                $asset->save();
            }

            return AssetMutation::create([
                'asset_id'                => $asset->id,
                'unit_id'                 => $asset->unit_id,
                'location_id'             => $asset->location_id,
                'tipe'                    => 'keluar',
                'jenis'                   => $jenis,
                'kondisi_sumber'          => $jenis === 'penggantian' ? $validated['kondisi_sumber'] : 'baik',
                'qty'                     => $qty,
                'peminjam'                => $jenis === 'peminjaman' ? $validated['peminjam'] : null,
                'kontak_peminjam'         => $jenis === 'peminjaman' ? ($validated['kontak_peminjam'] ?? null) : null,
                'tanggal_keluar'          => $validated['tanggal_keluar'],
                'tanggal_kembali_rencana' => $jenis === 'peminjaman' ? $validated['tanggal_kembali_rencana'] : null,
                'status_pinjam'           => $jenis === 'peminjaman' ? 'dipinjam' : null,
                'keterangan'              => $validated['keterangan'],
                'user_id'                 => $user->id,
                'report_id'               => $report->id,
            ]);
        });

        $mutation->load(['asset', 'unit']);
        NotificationService::barangKeluar($mutation, $user);

        $pesan = $jenis === 'penggantian'
            ? 'Laporan penggantian ' . $qty . ' unit barang rusak berhasil dibuat dan menunggu verifikasi Yayasan.'
            : 'Laporan peminjaman ' . $qty . ' unit barang berhasil dicatat.';

        return redirect()->route('laporan.keluar')->with('success', $pesan);
    }

    /**
     * Catat pengembalian barang yang dipinjamkan.
     *
     * ATURAN VALIDASI:
     *  - Hanya mutasi berjenis "peminjaman" dengan status "dipinjam".
     *  - tanggal_kembali wajib, tidak boleh mendahului tanggal keluar
     *    dan tidak boleh melewati hari ini.
     *  - kondisi_kembali menentukan mutasi stok:
     *      * baik          -> kondisi tidak berubah.
     *      * rusak_ringan  -> qty unit dipindahkan dari kondisi baik ke rusak ringan.
     *      * rusak_berat   -> qty unit dipindahkan dari kondisi baik ke rusak berat.
     *    total_qty tetap sama karena barang kembali ke ruangan.
     */
    public function kembalikan(Request $request, int $id)
    {
        $user = $this->currentUser();

        /** @var AssetMutation $mutation */
        $mutation = AssetMutation::with(['asset', 'unit'])->findOrFail($id);
        $this->guardUnit($mutation->unit_id);

        if (! $mutation->isPeminjaman()) {
            return back()->with('error', 'Mutasi ini bukan transaksi peminjaman barang.');
        }

        if ($mutation->status_pinjam !== 'dipinjam') {
            return back()->with('error', 'Peminjaman ini sudah ditandai dikembalikan sebelumnya.');
        }

        $validated = $request->validate([
            'tanggal_kembali'  => ['required', 'date', 'before_or_equal:today'],
            'kondisi_kembali'  => ['required', 'in:baik,rusak_ringan,rusak_berat'],
            'catatan_kembali'  => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal_kembali.before_or_equal' => 'Tanggal pengembalian tidak boleh melewati hari ini.',
        ], [
            'tanggal_kembali' => 'tanggal pengembalian',
            'kondisi_kembali' => 'kondisi barang saat kembali',
        ]);

        if ($mutation->tanggal_keluar
            && $validated['tanggal_kembali'] < $mutation->tanggal_keluar->format('Y-m-d')) {
            return back()->withErrors([
                'tanggal_kembali' => 'Tanggal pengembalian tidak boleh mendahului tanggal keluar ('
                    . $mutation->tanggal_keluar->format('d/m/Y') . ').',
            ]);
        }

        $asset = $mutation->asset;
        $qty   = (int) $mutation->qty;

        if ($validated['kondisi_kembali'] !== 'baik' && $asset && $asset->kondisi_baik < $qty) {
            return back()->withErrors([
                'kondisi_kembali' => 'Stok kondisi baik barang ini (' . $asset->kondisi_baik
                    . ' unit) lebih kecil dari jumlah yang dikembalikan (' . $qty
                    . ' unit), sehingga perubahan kondisi tidak dapat diproses. '
                    . 'Perbarui status kondisi barang terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($mutation, $asset, $qty, $validated, $user) {
            $mutation->update([
                'status_pinjam'          => 'dikembalikan',
                'tanggal_kembali_aktual' => $validated['tanggal_kembali'],
                'keterangan'             => trim($mutation->keterangan . ' | Dikembalikan '
                    . $validated['tanggal_kembali'] . ' (kondisi: '
                    . str_replace('_', ' ', $validated['kondisi_kembali']) . ')'
                    . (! empty($validated['catatan_kembali']) ? ' - ' . $validated['catatan_kembali'] : '')),
            ]);

            if ($asset && $validated['kondisi_kembali'] !== 'baik') {
                $kolom = $validated['kondisi_kembali'] === 'rusak_ringan'
                    ? 'kondisi_rusak_ringan'
                    : 'kondisi_rusak_berat';

                $asset->kondisi_baik = max(0, (int) $asset->kondisi_baik - $qty);
                $asset->{$kolom}     = (int) $asset->{$kolom} + $qty;
                $asset->syncTotalQty();
                $asset->save();
            }

            AssetMutation::create([
                'asset_id'       => $mutation->asset_id,
                'unit_id'        => $mutation->unit_id,
                'location_id'    => $mutation->location_id,
                'tipe'           => 'masuk',
                'jenis'          => 'pengembalian',
                'kondisi_sumber' => $validated['kondisi_kembali'],
                'qty'            => $qty,
                'peminjam'       => $mutation->peminjam,
                'tanggal_keluar' => $validated['tanggal_kembali'],
                'keterangan'     => 'Pengembalian barang pinjaman oleh ' . $mutation->peminjam
                    . ' (kondisi: ' . str_replace('_', ' ', $validated['kondisi_kembali']) . ')'
                    . (! empty($validated['catatan_kembali']) ? ' - ' . $validated['catatan_kembali'] : ''),
                'user_id'        => $user->id,
                'report_id'      => $mutation->report_id,
            ]);
        });

        NotificationService::barangDikembalikan($mutation->fresh(['asset', 'unit']), $user);

        return redirect()->route('laporan.keluar')
            ->with('success', 'Pengembalian ' . $qty . ' unit barang pinjaman berhasil dicatat.');
    }

    /* ── HAPUS DATA MUTASI ──────────────────────────────────── */

    public function destroy(int $id)
    {
        $mutation = AssetMutation::findOrFail($id);
        $this->guardUnit($mutation->unit_id);
        $mutation->delete();

        return redirect()->back()->with('success', 'Data mutasi barang berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:asset_mutations,id'],
        ], [
            'ids.required' => 'Pilih setidaknya satu data mutasi yang ingin dihapus.',
            'ids.min'      => 'Pilih setidaknya satu data mutasi yang ingin dihapus.',
        ]);

        $query = AssetMutation::whereIn('id', $validated['ids']);
        $this->scopeUnit($query);
        $count = $query->delete();

        return redirect()->back()->with('success', $count . ' data mutasi barang berhasil dihapus.');
    }

    /* ── Helper ─────────────────────────────────────────────── */

    /**
     * Daftar barang untuk dropdown, sudah dilengkapi data ketersediaan.
     */
    private function assetOptions()
    {
        $query = Asset::with(['location', 'category', 'merek'])->orderBy('nama_barang');
        $this->scopeUnit($query);

        return $query->get()->map(function (Asset $asset) {
            $asset->setAttribute('qty_dipinjam', $asset->qtyDipinjam());
            $asset->setAttribute('qty_siap_pinjam', $asset->qtySiapPinjam());

            return $asset;
        });
    }

    /**
     * @param  array<int, string>  $allowedJenis
     * @return array<string, string|int|null>
     */
    private function filters(Request $request, array $allowedJenis): array
    {
        $jenis = (string) $request->query('jenis', 'semua');
        if (! in_array($jenis, array_merge(['semua'], $allowedJenis), true)) {
            $jenis = 'semua';
        }

        $status = (string) $request->query('status_pinjam', 'semua');
        if (! in_array($status, ['semua', 'dipinjam', 'dikembalikan'], true)) {
            $status = 'semua';
        }

        $unit = $request->query('unit');

        return [
            'jenis'         => $jenis,
            'status_pinjam' => $status,
            'unit'          => is_numeric($unit) ? (int) $unit : null,
            'q'             => trim((string) $request->query('q', '')),
            'dari'          => $this->validDate($request->query('dari')),
            'sampai'        => $this->validDate($request->query('sampai')),
        ];
    }

    private function validDate($value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /**
     * @param  array<string, string|int|null>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['jenis'] !== 'semua') {
            $query->where('jenis', $filters['jenis']);
        }

        if ($filters['status_pinjam'] !== 'semua') {
            $query->where('status_pinjam', $filters['status_pinjam']);
        }

        if ($this->isSuperAdmin() && ! empty($filters['unit'])) {
            $query->where('unit_id', $filters['unit']);
        }

        if (! empty($filters['q'])) {
            $keyword = $filters['q'];
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('keterangan', 'like', "%{$keyword}%")
                    ->orWhere('peminjam', 'like', "%{$keyword}%")
                    ->orWhereHas('asset', function (Builder $a) use ($keyword) {
                        $a->where('nama_barang', 'like', "%{$keyword}%")
                            ->orWhere('kode_barang', 'like', "%{$keyword}%");
                    });
            });
        }

        if (! empty($filters['dari'])) {
            $query->whereDate('created_at', '>=', $filters['dari']);
        }

        if (! empty($filters['sampai'])) {
            $query->whereDate('created_at', '<=', $filters['sampai']);
        }
    }

    /**
     * @return array<string, int>
     */
    private function ringkasan(string $tipe): array
    {
        $base = fn () => $this->scopeUnit(AssetMutation::query())->where('tipe', $tipe);

        return [
            'transaksi' => $base()->count(),
            'unit'      => (int) $base()->sum('qty'),
            'bulan_ini' => $base()->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function ringkasanKeluar(): array
    {
        $base = fn () => $this->scopeUnit(AssetMutation::query())->where('tipe', 'keluar');

        return [
            'transaksi'    => $base()->count(),
            'penggantian'  => (int) $base()->where('jenis', 'penggantian')->sum('qty'),
            'dipinjam'     => (int) $base()->where('jenis', 'peminjaman')
                                ->where('status_pinjam', 'dipinjam')->sum('qty'),
            'dikembalikan' => (int) $base()->where('jenis', 'peminjaman')
                                ->where('status_pinjam', 'dikembalikan')->sum('qty'),
            'terlambat'    => $base()->where('jenis', 'peminjaman')
                                ->where('status_pinjam', 'dipinjam')
                                ->whereNotNull('tanggal_kembali_rencana')
                                ->whereDate('tanggal_kembali_rencana', '<', now()->toDateString())
                                ->count(),
        ];
    }
}
