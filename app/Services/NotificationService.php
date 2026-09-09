<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetMutation;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Pusat pembuatan notifikasi in-app.
 *
 * Aturan penerima:
 *  - Aktivitas dari Admin Unit  -> diberitahukan ke seluruh Super Admin Yayasan.
 *  - Aktivitas dari Yayasan     -> diberitahukan ke pelapor + Admin Unit terkait.
 * Aktor yang memicu aksi tidak pernah menerima notifikasinya sendiri.
 */
class NotificationService
{
    /**
     * Seluruh akun Super Admin Yayasan.
     *
     * @return Collection<int, User>
     */
    public static function superAdmins(): Collection
    {
        return User::query()
            ->whereIn('role', ['super_admin', 'admin_yayasan'])
            ->orWhere(function ($q) {
                $q->whereNull('unit_id')->where('role', '<>', 'admin_unit');
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Seluruh Admin Unit pada satu unit sekolah.
     *
     * @return Collection<int, User>
     */
    public static function unitAdmins(?int $unitId): Collection
    {
        if (! $unitId) {
            return collect();
        }

        return User::query()->where('unit_id', $unitId)->get();
    }

    /**
     * Membuat satu baris notifikasi untuk sekumpulan penerima.
     *
     * @param  Collection<int, User>|array<int, User>  $recipients
     */
    public static function push($recipients, array $payload, ?int $exceptUserId = null): int
    {
        $created = 0;

        foreach ($recipients as $user) {
            if (! $user instanceof User) {
                continue;
            }
            if ($exceptUserId !== null && $user->id === $exceptUserId) {
                continue;
            }

            Notification::create(array_merge([
                'user_id' => $user->id,
                'tipe'    => 'info',
                'judul'   => 'Notifikasi',
                'pesan'   => '',
            ], $payload));

            $created++;
        }

        return $created;
    }

    /* -- Event: Laporan -------------------------------------- */

    /** Laporan baru dibuat oleh Admin Unit. */
    public static function laporanDibuat(Report $report, ?User $actor = null): void
    {
        $unitLabel = $report->unit?->label() ?? 'Unit';

        self::push(self::superAdmins(), [
            'unit_id'   => $report->unit_id,
            'report_id' => $report->id,
            'asset_id'  => $report->asset_id,
            'tipe'      => 'laporan_baru',
            'judul'     => '[' . $unitLabel . '] ' . $report->jenisLabel() . ' baru',
            'pesan'     => $report->judul . ' - dilaporkan oleh ' . ($actor->name ?? 'Admin Unit')
                            . ' dan menunggu verifikasi Yayasan.',
            'url'       => route('laporan.index', ['status' => 'pending']),
            'icon'      => 'file-plus',
        ], $actor?->id);
    }

    /** Status / verifikasi laporan diperbarui oleh Admin Yayasan. */
    public static function laporanDiverifikasi(Report $report, ?User $actor = null): void
    {
        $tipe = match (true) {
            $report->isRejected() => 'laporan_ditolak',
            $report->isApproved() => 'laporan_disetujui',
            $report->isPending()  => 'laporan_baru',
            default               => 'laporan_proses',
        };

        $judul = match ($tipe) {
            'laporan_disetujui' => 'Laporan Anda telah disetujui Yayasan',
            'laporan_ditolak'   => 'Laporan Anda ditolak Yayasan',
            'laporan_baru'      => 'Laporan Anda dibuka ulang & menunggu verifikasi',
            default             => 'Laporan Anda sedang diproses Yayasan',
        };

        $url = $report->isSelesai()
            ? route('laporan.selesai')
            : route('laporan.index');

        $recipients = self::unitAdmins($report->unit_id);
        if ($report->user && ! $recipients->contains('id', $report->user_id)) {
            $recipients->push($report->user);
        }

        self::push($recipients, [
            'unit_id'   => $report->unit_id,
            'report_id' => $report->id,
            'asset_id'  => $report->asset_id,
            'tipe'      => $tipe,
            'judul'     => $judul,
            'pesan'     => $report->judul . ' - status: ' . $report->statusLabel()
                            . ($report->tanggapan ? '. Tanggapan: ' . $report->tanggapan : '.'),
            'url'       => $url,
            'icon'      => null,
        ], $actor?->id);
    }

    /* -- Event: Mutasi Aset ---------------------------------- */

    /** Barang baru masuk / bertambah. */
    public static function barangMasuk(Asset $asset, int $qty, ?User $actor = null): void
    {
        $unitLabel = $asset->unit?->label() ?? 'Unit';

        self::push(self::superAdmins(), [
            'unit_id'  => $asset->unit_id,
            'asset_id' => $asset->id,
            'tipe'     => 'barang_masuk',
            'judul'    => '[' . $unitLabel . '] Barang masuk: ' . $asset->nama_barang,
            'pesan'    => $qty . ' unit ' . $asset->nama_barang . ' (' . $asset->kode_barang . ') '
                            . 'dicatat masuk ke ' . ($asset->location?->nama ?? 'ruangan')
                            . ' oleh ' . ($actor->name ?? 'Admin Unit') . '.',
            'url'      => route('laporan.masuk'),
            'icon'     => 'arrow-down-left-square',
        ], $actor?->id);
    }

    /** Barang keluar (penggantian barang rusak / peminjaman). */
    public static function barangKeluar(AssetMutation $mutation, ?User $actor = null): void
    {
        $asset     = $mutation->asset;
        $unitLabel = $mutation->unit?->label() ?? 'Unit';
        $tipe      = $mutation->isPeminjaman() ? 'peminjaman' : 'barang_keluar';

        $pesan = $mutation->qty . ' unit ' . ($asset->nama_barang ?? 'barang')
            . ' (' . ($asset->kode_barang ?? '-') . ') dicatat keluar - ' . $mutation->jenisLabel();

        if ($mutation->isPeminjaman()) {
            $pesan .= ' oleh ' . $mutation->peminjam;
            if ($mutation->tanggal_kembali_rencana) {
                $pesan .= ', rencana kembali ' . $mutation->tanggal_kembali_rencana->format('d M Y');
            }
        }

        self::push(self::superAdmins(), [
            'unit_id'   => $mutation->unit_id,
            'asset_id'  => $mutation->asset_id,
            'report_id' => $mutation->report_id,
            'tipe'      => $tipe,
            'judul'     => '[' . $unitLabel . '] ' . $mutation->jenisLabel(),
            'pesan'     => $pesan . '.',
            'url'       => route('laporan.keluar'),
            'icon'      => null,
        ], $actor?->id);
    }

    /** Barang pinjaman dikembalikan. */
    public static function barangDikembalikan(AssetMutation $mutation, ?User $actor = null): void
    {
        $asset     = $mutation->asset;
        $unitLabel = $mutation->unit?->label() ?? 'Unit';

        self::push(self::superAdmins(), [
            'unit_id'  => $mutation->unit_id,
            'asset_id' => $mutation->asset_id,
            'tipe'     => 'pengembalian',
            'judul'    => '[' . $unitLabel . '] Barang pinjaman dikembalikan',
            'pesan'    => $mutation->qty . ' unit ' . ($asset->nama_barang ?? 'barang')
                            . ' yang dipinjam ' . $mutation->peminjam . ' telah dikembalikan pada '
                            . optional($mutation->tanggal_kembali_aktual)->format('d M Y') . '.',
            'url'      => route('laporan.keluar'),
            'icon'     => 'undo-2',
        ], $actor?->id);
    }
}
