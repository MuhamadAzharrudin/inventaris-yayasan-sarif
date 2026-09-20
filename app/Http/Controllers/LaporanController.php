<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\Report;
use App\Models\Unit;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pelaporan kerusakan, penggantian & peminjaman barang.
 *
 * Alur:
 *  1. Admin Unit membuat laporan  -> status "pending" (menunggu verifikasi).
 *  2. Admin Yayasan menindaklanjuti -> "proses" atau langsung memverifikasi.
 *  3. Verifikasi Yayasan          -> status "selesai" + hasil disetujui / ditolak.
 *
 * Admin Unit hanya boleh MELIHAT status; perubahan status adalah
 * kewenangan Admin Yayasan (lihat updateStatus()).
 */
class LaporanController extends Controller
{
    use HandlesUnitScope;

    /**
     * Cek Pelaporan (Admin Unit) / Laporan & Audit Global (Admin Yayasan).
     */
    public function index(Request $request)
    {
        $filters = $this->filters($request, 'aktif');

        $query = Report::with(['asset.category', 'asset.merek', 'location', 'unit', 'user', 'verifier'])
            ->latest();

        $this->scopeUnit($query);
        $this->applyFilters($query, $filters);

        $reports = $query->paginate(10)->withQueryString();

        return view('laporan.index', [
            'reports'  => $reports,
            'filters'  => $filters,
            'units'    => $this->isSuperAdmin() ? Unit::orderBy('id')->get() : collect(),
            'counters' => $this->counters(),
        ]);
    }

    /**
     * Arsip laporan yang telah diverifikasi Admin Yayasan.
     */
    public function selesai(Request $request)
    {
        $filters = $this->filters($request, 'selesai');
        $filters['status'] = 'selesai';

        $query = Report::with(['asset.category', 'asset.merek', 'location', 'unit', 'user', 'verifier'])
            ->latest('verified_at');

        $this->scopeUnit($query);
        $this->applyFilters($query, $filters);

        $reports = $query->paginate(10)->withQueryString();

        return view('laporan.selesai', [
            'reports'  => $reports,
            'filters'  => $filters,
            'units'    => $this->isSuperAdmin() ? Unit::orderBy('id')->get() : collect(),
            'counters' => $this->counters(),
        ]);
    }

    /**
     * Form laporan kerusakan (khusus Admin Unit).
     */
    public function create()
    {
        $query = Asset::with(['category', 'merek', 'location'])->orderBy('nama_barang');
        $this->scopeUnit($query);

        return view('laporan.create', ['assets' => $query->get()]);
    }

    /**
     * Simpan laporan kerusakan baru.
     */
    public function store(Request $request)
    {
        $user = $this->currentUser();

        $validated = $request->validate([
            'asset_id'          => ['required', 'exists:assets,id'],
            'judul'             => ['required', 'string', 'max:255'],
            'tingkat_kerusakan' => ['nullable', 'in:rusak_ringan,rusak_berat'],
            'deskripsi'         => ['required', 'string', 'max:2000'],
            'qty'               => ['nullable', 'integer', 'min:1'],
        ], [], [
            'asset_id'          => 'barang',
            'judul'             => 'judul pelaporan',
            'tingkat_kerusakan' => 'tingkat kerusakan',
            'deskripsi'         => 'deskripsi pelaporan',
            'qty'               => 'jumlah unit terdampak',
        ]);

        /** @var Asset $asset */
        $asset = Asset::with('location')->findOrFail($validated['asset_id']);
        $this->guardUnit($asset->unit_id);

        // Jumlah unit terdampak tidak boleh melebihi stok barang tersebut.
        $qty = (int) ($validated['qty'] ?? 1);
        if ($qty > $asset->total_qty) {
            return back()->withInput()->withErrors([
                'qty' => 'Jumlah unit terdampak (' . $qty . ') melebihi total stok barang yang hanya '
                    . $asset->total_qty . ' unit.',
            ]);
        }

        $tingkat = $validated['tingkat_kerusakan'] ?? 'rusak_ringan';
        $report  = null;

        DB::transaction(function () use ($asset, $qty, $tingkat, $validated, $user, &$report) {
            // Update kondisi barang: pindahkan unit baik ke kondisi rusak jika barang awalnya tercatat baik
            if ($asset->kondisi_baik > 0) {
                $pindah = min($asset->kondisi_baik, $qty);
                $asset->kondisi_baik = max(0, $asset->kondisi_baik - $pindah);
                if ($tingkat === 'rusak_berat') {
                    $asset->kondisi_rusak_berat += $pindah;
                } else {
                    $asset->kondisi_rusak_ringan += $pindah;
                }
                $asset->syncTotalQty();
                $asset->save();
            } elseif ($asset->kondisi_rusak_ringan == 0 && $asset->kondisi_rusak_berat == 0) {
                if ($tingkat === 'rusak_berat') {
                    $asset->kondisi_rusak_berat = $qty;
                } else {
                    $asset->kondisi_rusak_ringan = $qty;
                }
                $asset->syncTotalQty();
                $asset->save();
            }

            $report = Report::create([
                'unit_id'     => $asset->unit_id,
                'asset_id'    => $asset->id,
                'location_id' => $asset->location_id,
                'user_id'     => $user->id,
                'jenis'       => 'kerusakan',
                'judul'       => $validated['judul'],
                'deskripsi'   => $validated['deskripsi'],
                'qty'         => $qty,
                'status'      => 'pending',
                'verifikasi'  => null,
            ]);
        });

        NotificationService::laporanDibuat($report->fresh(['unit']), $user);

        return redirect()->route('laporan.index')
            ->with('success', 'Laporan kerusakan berhasil dikirim dan barang otomatis tercatat pada menu Barang Rusak.');
    }

    /**
     * Verifikasi / pembaruan status laporan.
     *
     * Aturan validasi status:
     *  - Hanya Admin Yayasan yang boleh mengubah status (middleware super_admin).
     *  - aksi "proses"   : status pending|proses -> proses, verifikasi dikosongkan.
     *  - aksi "setujui"  : status apa pun        -> selesai + verifikasi disetujui.
     *  - aksi "tolak"    : status apa pun        -> selesai + verifikasi ditolak,
     *                      wajib menyertakan alasan pada kolom tanggapan.
     *  - aksi "buka"     : laporan selesai       -> pending, verifikasi dibatalkan.
     */
    public function updateStatus(Request $request, int $id)
    {
        $report = Report::with(['unit', 'user', 'asset'])->findOrFail($id);

        $validated = $request->validate([
            'aksi'      => ['required', 'in:proses,setujui,tolak,buka'],
            'tanggapan' => ['nullable', 'string', 'max:2000'],
        ], [
            'aksi.required' => 'Aksi verifikasi wajib dipilih.',
            'aksi.in'       => 'Aksi verifikasi tidak dikenali.',
        ]);

        $aksi      = $validated['aksi'];
        $tanggapan = trim((string) ($validated['tanggapan'] ?? ''));

        // Penolakan wajib disertai alasan agar unit sekolah tahu tindak lanjutnya.
        if ($aksi === 'tolak' && $tanggapan === '') {
            return back()->withInput()->withErrors([
                'tanggapan' => 'Alasan penolakan wajib diisi agar Admin Unit mengetahui penyebabnya.',
            ]);
        }

        // Laporan yang sudah selesai tidak boleh dikembalikan ke "proses"
        // tanpa dibuka ulang terlebih dahulu.
        if ($aksi === 'proses' && $report->isSelesai()) {
            return back()->withErrors([
                'aksi' => 'Laporan sudah berstatus selesai. Gunakan tombol "Buka Ulang" '
                    . 'sebelum memprosesnya kembali.',
            ]);
        }

        if ($aksi === 'buka' && ! $report->isSelesai()) {
            return back()->withErrors([
                'aksi' => 'Hanya laporan berstatus selesai yang dapat dibuka ulang.',
            ]);
        }

        switch ($aksi) {
            case 'proses':
                $report->status      = 'proses';
                $report->verifikasi  = null;
                $report->verified_by = null;
                $report->verified_at = null;
                break;

            case 'setujui':
                $report->status      = 'selesai';
                $report->verifikasi  = 'disetujui';
                $report->verified_by = $this->currentUser()->id;
                $report->verified_at = now();
                break;

            case 'tolak':
                $report->status      = 'selesai';
                $report->verifikasi  = 'ditolak';
                $report->verified_by = $this->currentUser()->id;
                $report->verified_at = now();
                break;

            case 'buka':
                $report->status      = 'pending';
                $report->verifikasi  = null;
                $report->verified_by = null;
                $report->verified_at = null;
                break;
        }

        if ($tanggapan !== '') {
            $report->tanggapan = $tanggapan;
        }

        $report->save();

        NotificationService::laporanDiverifikasi($report->fresh(['unit', 'user']), $this->currentUser());

        $pesan = match ($aksi) {
            'proses'  => 'Laporan ditandai sedang diproses Yayasan.',
            'setujui' => 'Laporan berhasil diverifikasi & disetujui.',
            'tolak'   => 'Laporan ditandai ditolak beserta alasannya.',
            'buka'    => 'Laporan dibuka ulang dan kembali menunggu verifikasi.',
        };

        return redirect()->back()->with('success', $pesan);
    }

    /**
     * Hapus laporan (khusus Admin Yayasan / Super Admin).
     */
    public function destroy(int $id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return redirect()->back()->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Hapus banyak laporan sekaligus (khusus Admin Yayasan / Super Admin).
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:reports,id'],
        ], [
            'ids.required' => 'Pilih setidaknya satu laporan yang ingin dihapus.',
            'ids.min'      => 'Pilih setidaknya satu laporan yang ingin dihapus.',
        ]);

        $count = Report::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', $count . ' laporan berhasil dihapus.');
    }

    /**
     * Cetak satu laporan (HTML siap print / simpan PDF dari peramban).
     */
    public function cetakPdf(int $id)
    {
        $report = Report::with(['asset.category', 'asset.merek', 'location', 'unit', 'user', 'verifier'])
            ->findOrFail($id);

        $this->guardUnit($report->unit_id);

        return view('laporan.pdf-single', compact('report'));
    }

    /**
     * Unduh rekap laporan (CSV) sesuai filter audit yang aktif.
     */
    public function export(Request $request)
    {
        $filters = $this->filters($request, 'semua');

        $query = Report::with(['asset', 'location', 'unit', 'user', 'verifier'])->latest();
        $this->scopeUnit($query);
        $this->applyFilters($query, $filters);

        $reports  = $query->get();
        $filename = 'Rekap_Laporan_Audit_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($reports) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'No', 'Tanggal Lapor', 'Unit', 'Jenis Laporan', 'Judul', 'Barang', 'Kode Barang',
                'Lokasi', 'Qty', 'Status', 'Hasil Verifikasi', 'Diverifikasi Oleh',
                'Tanggal Verifikasi', 'Pelapor', 'Tanggapan',
            ]);

            foreach ($reports as $i => $r) {
                fputcsv($out, [
                    $i + 1,
                    $r->created_at?->format('d/m/Y H:i'),
                    $r->unit?->label() ?? '-',
                    $r->jenisLabel(),
                    $r->judul,
                    $r->asset->nama_barang ?? '-',
                    $r->asset->kode_barang ?? '-',
                    $r->location->nama ?? '-',
                    $r->qty ?? '-',
                    $r->statusLabel(),
                    $r->verifikasi ? ucfirst($r->verifikasi) : 'Belum diverifikasi',
                    $r->verifier->name ?? '-',
                    $r->verified_at?->format('d/m/Y H:i') ?? '-',
                    $r->user->name ?? '-',
                    $r->tanggapan ?? '-',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /* ── Helper filter & statistik ──────────────────────────── */

    /**
     * Normalisasi parameter filter dari query string.
     *
     * @return array<string, string|null>
     */
    private function filters(Request $request, string $defaultStatus): array
    {
        $status = (string) $request->query('status', $defaultStatus);
        if (! in_array($status, ['aktif', 'semua', 'pending', 'proses', 'selesai'], true)) {
            $status = $defaultStatus;
        }

        $jenis = (string) $request->query('jenis', 'semua');
        if (! in_array($jenis, array_merge(['semua'], Report::JENIS), true)) {
            $jenis = 'semua';
        }

        $verifikasi = (string) $request->query('verifikasi', 'semua');
        if (! in_array($verifikasi, ['semua', 'belum', 'disetujui', 'ditolak'], true)) {
            $verifikasi = 'semua';
        }

        $unit = $request->query('unit');
        $unit = is_numeric($unit) ? (int) $unit : null;

        return [
            'status'     => $status,
            'jenis'      => $jenis,
            'verifikasi' => $verifikasi,
            'unit'       => $unit,
            'q'          => Str::limit(trim((string) $request->query('q', '')), 100, ''),
            'dari'       => $this->validDate($request->query('dari')),
            'sampai'     => $this->validDate($request->query('sampai')),
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
        if ($filters['status'] === 'aktif') {
            $query->whereIn('status', ['pending', 'proses']);
        } elseif (in_array($filters['status'], ['pending', 'proses', 'selesai'], true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['jenis'] !== 'semua') {
            $query->where('jenis', $filters['jenis']);
        }

        if ($filters['verifikasi'] === 'belum') {
            $query->whereNull('verifikasi');
        } elseif (in_array($filters['verifikasi'], ['disetujui', 'ditolak'], true)) {
            $query->where('verifikasi', $filters['verifikasi']);
        }

        if ($this->isSuperAdmin() && ! empty($filters['unit'])) {
            $query->where('unit_id', $filters['unit']);
        }

        if (! empty($filters['q'])) {
            $keyword = $filters['q'];
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                    ->orWhere('deskripsi', 'like', "%{$keyword}%")
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
     * Ringkasan jumlah laporan per status pada cakupan data pengguna.
     *
     * @return array<string, int>
     */
    private function counters(): array
    {
        $base = fn () => $this->scopeUnit(Report::query());

        return [
            'pending'   => (clone $base())->where('status', 'pending')->count(),
            'proses'    => (clone $base())->where('status', 'proses')->count(),
            'selesai'   => (clone $base())->where('status', 'selesai')->count(),
            'disetujui' => (clone $base())->where('verifikasi', 'disetujui')->count(),
            'ditolak'   => (clone $base())->where('verifikasi', 'ditolak')->count(),
            'total'     => (clone $base())->count(),
        ];
    }
}
