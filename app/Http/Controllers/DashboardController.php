<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUnitScope;
use App\Models\Asset;
use App\Models\AssetMutation;
use App\Models\Location;
use App\Models\Report;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    use HandlesUnitScope;

    /**
     * Arahkan pengguna ke dashboard sesuai role & unitnya.
     */
    public function index(Request $request)
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            return $this->yayasan();
        }

        return $this->unitDashboard($user->unit_id, $user->unitKode() ?? 'mi');
    }

    public function yayasan()
    {
        $units = Unit::orderBy('id')->get();

        $assets     = Asset::query()->get(['unit_id', 'total_qty', 'kondisi_baik', 'kondisi_rusak_ringan', 'kondisi_rusak_berat', 'nilai_estimasi']);
        $totalAset  = (int) $assets->sum('total_qty');
        $baik       = (int) $assets->sum('kondisi_baik');
        $ringan     = (int) $assets->sum('kondisi_rusak_ringan');
        $berat      = (int) $assets->sum('kondisi_rusak_berat');
        $totalNilai = (float) $assets->sum('nilai_estimasi');

        $perUnit = $units->map(function (Unit $unit) use ($assets, $totalAset) {
            $milik = $assets->where('unit_id', $unit->id);
            $qty   = (int) $milik->sum('total_qty');

            return [
                'id'           => $unit->id,
                'kode'         => $unit->label(),
                'nama'         => $unit->nama,
                'jenjang'      => $unit->jenjang(),
                'icon'         => $unit->icon(),
                'jenis_barang' => $milik->count(),
                'total_qty'    => $qty,
                'baik'         => (int) $milik->sum('kondisi_baik'),
                'rusak_ringan' => (int) $milik->sum('kondisi_rusak_ringan'),
                'rusak_berat'  => (int) $milik->sum('kondisi_rusak_berat'),
                'nilai'        => (float) $milik->sum('nilai_estimasi'),
                'ruangan'      => Location::where('unit_id', $unit->id)->count(),
                'persen'       => $totalAset > 0 ? round($qty / $totalAset * 100, 1) : 0.0,
                'url'          => route('unit.show', $unit->id),
            ];
        });

        return view('dashboard.yayasan', [
            'stats' => [
                'total_aset'        => $totalAset,
                'total_jenis'       => $assets->count(),
                'total_nilai'       => 'Rp ' . number_format($totalNilai, 0, ',', '.'),
                'aset_baik'         => $baik,
                'aset_rusak_ringan' => $ringan,
                'aset_rusak_berat'  => $berat,
                'persen_baik'       => $totalAset > 0 ? round($baik / $totalAset * 100, 1) : 0.0,
                'total_ruangan'     => Location::count(),
                'laporan_pending'   => Report::where('status', 'pending')->count(),
                'laporan_proses'    => Report::where('status', 'proses')->count(),
                'laporan_selesai'   => Report::where('status', 'selesai')->count(),
            ],
            'perUnit'          => $perUnit,
            'laporanTerbaru'   => Report::with(['unit', 'user', 'asset'])
                                    ->whereIn('status', ['pending', 'proses'])
                                    ->latest()->take(5)->get(),
            'recentActivities' => $this->recentActivities(null),
        ]);
    }

    public function mi()
    {
        return $this->unitDashboardByKode('mi');
    }

    public function smp()
    {
        return $this->unitDashboardByKode('smp');
    }

    public function smk()
    {
        return $this->unitDashboardByKode('smk');
    }

    /* ── Internal ───────────────────────────────────────────── */

    private function unitDashboardByKode(string $kode)
    {
        $unit = Unit::where('kode', $kode)->first();

        // Admin Unit tidak boleh membuka dashboard unit lain.
        $current = $this->currentUnitId();
        if ($current !== null && $unit && (int) $unit->id !== (int) $current) {
            abort(403, 'Anda hanya dapat mengakses dashboard unit sekolah Anda sendiri.');
        }

        // Admin Yayasan diarahkan ke halaman detail inventaris unit,
        // bukan masuk ke dashboard sekolah.
        if ($this->isSuperAdmin() && $unit) {
            return redirect()->route('unit.show', $unit->id);
        }

        return $this->unitDashboard($unit->id ?? null, $kode === 'smp' ? 'mts' : $kode);
    }

    private function unitDashboard(?int $unitId, string $kodeView)
    {
        $unit = $unitId ? Unit::find($unitId) : null;

        $assets = Asset::with('location')->where('unit_id', $unitId)->get();

        $locations   = Location::withCount('assets')->where('unit_id', $unitId)->orderBy('nama')->get();
        $ruanganList = $locations->map(function (Location $loc) use ($assets) {
            $milik  = $assets->where('location_id', $loc->id);
            $ringan = (int) $milik->sum('kondisi_rusak_ringan');
            $berat  = (int) $milik->sum('kondisi_rusak_berat');

            return [
                'id'           => $loc->slug,
                'nama'         => $loc->nama,
                'tipe'         => $loc->tipe,
                'jenis'        => $milik->count(),
                'jumlah'       => (int) $milik->sum('total_qty'),
                'rusak_ringan' => $ringan,
                'rusak_berat'  => $berat,
                'kondisi'      => $berat > 0 ? 'Ada Rusak Berat' : ($ringan > 0 ? 'Butuh Perbaikan' : 'Baik'),
            ];
        });

        $totalQty = (int) $assets->sum('total_qty');
        $baik     = (int) $assets->sum('kondisi_baik');

        return view('dashboard.unit', [
            'kodeUnit' => in_array($kodeView, ['mts', 'smp'], true) ? 'mts' : ($kodeView === 'smk' ? 'smk' : 'mi'),
            'unit'  => $unit,
            'stats' => [
                'total_aset'        => $totalQty,
                'total_jenis'       => $assets->count(),
                'aset_baik'         => $baik,
                'aset_rusak_ringan' => (int) $assets->sum('kondisi_rusak_ringan'),
                'aset_rusak_berat'  => (int) $assets->sum('kondisi_rusak_berat'),
                'persen_baik'       => $totalQty > 0 ? round($baik / $totalQty * 100, 1) : 0.0,
                'total_ruangan'     => $locations->count(),
                'total_nilai'       => 'Rp ' . number_format((float) $assets->sum('nilai_estimasi'), 0, ',', '.'),
                'laporan_pending'   => Report::where('unit_id', $unitId)->where('status', 'pending')->count(),
                'laporan_proses'    => Report::where('unit_id', $unitId)->where('status', 'proses')->count(),
                'laporan_selesai'   => Report::where('unit_id', $unitId)->where('status', 'selesai')->count(),
                'dipinjam'          => (int) AssetMutation::where('unit_id', $unitId)
                                            ->where('jenis', 'peminjaman')
                                            ->where('status_pinjam', 'dipinjam')->sum('qty'),
            ],
            'ruanganList'      => $ruanganList,
            'laporanTerbaru'   => Report::with(['asset', 'user'])->where('unit_id', $unitId)
                                    ->latest()->take(5)->get(),
            'recentActivities' => $this->recentActivities($unitId),
        ]);
    }

    /**
     * Log aktivitas gabungan (mutasi aset + laporan) yang benar-benar terjadi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recentActivities(?int $unitId): Collection
    {
        $mutasiQuery = AssetMutation::with(['asset', 'unit', 'user', 'location'])->latest()->take(8);
        $laporanQuery = Report::with(['unit', 'user', 'asset'])->latest()->take(8);

        if ($unitId !== null) {
            $mutasiQuery->where('unit_id', $unitId);
            $laporanQuery->where('unit_id', $unitId);
        }

        $mutasi = $mutasiQuery->get()->map(fn (AssetMutation $m) => [
            'waktu'  => $m->created_at,
            'time'   => $m->created_at?->diffForHumans(),
            'unit'   => $m->unit?->label() ?? 'Yayasan',
            'desc'   => $m->jenisLabel() . ': ' . $m->qty . ' unit '
                        . ($m->asset->nama_barang ?? 'barang')
                        . ' (' . ($m->location->nama ?? 'ruangan') . ')',
            'user'   => $m->user->name ?? 'Sistem',
            'status' => $m->tipe === 'masuk' ? 'Masuk' : 'Keluar',
            'icon'   => $m->tipe === 'masuk' ? 'arrow-down-left-square' : 'arrow-up-right-square',
        ]);

        $laporan = $laporanQuery->get()->map(fn (Report $r) => [
            'waktu'  => $r->created_at,
            'time'   => $r->created_at?->diffForHumans(),
            'unit'   => $r->unit?->label() ?? 'Yayasan',
            'desc'   => $r->jenisLabel() . ': ' . $r->judul,
            'user'   => $r->user->name ?? 'Admin',
            'status' => $r->isSelesai() ? 'Selesai' : ($r->isProses() ? 'Proses' : 'Pending'),
            'icon'   => 'clipboard-list',
        ]);

        return $mutasi->concat($laporan)
            ->sortByDesc('waktu')
            ->take(8)
            ->values();
    }
}
