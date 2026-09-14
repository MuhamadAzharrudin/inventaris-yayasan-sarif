<?php

namespace Database\Seeders;

use App\Helpers\QrCodeHelper;
use App\Models\Asset;
use App\Models\AssetMutation;
use App\Models\Category;
use App\Models\Location;
use App\Models\Merek;
use App\Models\Notification;
use App\Models\Report;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal Sistem Inventaris Sarana & Prasarana
 * Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror.
 *
 * Struktur ruangan mengikuti kondisi terkini tiap unit:
 *  - MI  : Ruang Kelas 1 s.d. 6, Ruang Guru.
 *  - MTS : Ruang Kelas 8 & 9, Laboratorium Komputer, Aula, Ruang Guru.
 *  - SMK : Ruang Kelas 10 s.d. 12, Laboratorium Komputer, Aula, Ruang Guru.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* ── 1. Unit Sekolah ─────────────────────────────────── */
        $mi  = Unit::firstOrCreate(['kode' => 'mi'],  ['nama' => 'MI Husnul Abror',  'kepala_unit' => 'Ustadz Ahmad, S.Pd.I']);
        $mts = Unit::firstOrCreate(['kode' => 'smp'], ['nama' => 'MTS Husnul Abror', 'kepala_unit' => 'Drs. H. Abdullah']);
        $smk = Unit::firstOrCreate(['kode' => 'smk'], ['nama' => 'SMK Husnul Abror', 'kepala_unit' => 'Ir. Muhammad Ridwan, M.T.']);

        /* ── 2. Akun Pengguna ────────────────────────────────── */
        $yayasanUser = User::create([
            'name'     => 'Admin Yayasan Husnul Abror',
            'email'    => 'adminyayasan@husnulabror.sch.id',
            'password' => Hash::make('password'),
            'role'     => 'super_admin',
            'unit_id'  => null,
        ]);

        $miUser = User::create([
            'name'     => 'Admin MI Husnul Abror',
            'email'    => 'admin.mi@husnulabror.sch.id',
            'password' => Hash::make('password'),
            'role'     => 'admin_unit',
            'unit_id'  => $mi->id,
        ]);

        $mtsUser = User::create([
            'name'     => 'Admin MTS Husnul Abror',
            'email'    => 'admin.smp@husnulabror.sch.id',
            'password' => Hash::make('password'),
            'role'     => 'admin_unit',
            'unit_id'  => $mts->id,
        ]);

        $smkUser = User::create([
            'name'     => 'Admin SMK Husnul Abror',
            'email'    => 'admin.smk@husnulabror.sch.id',
            'password' => Hash::make('password'),
            'role'     => 'admin_unit',
            'unit_id'  => $smk->id,
        ]);

        /* ── 3. Kategori Barang (berlaku lintas unit) ─────────── */
        $catMeubel     = Category::create(['unit_id' => null, 'nama' => 'Meubeler & Mebel', 'kode' => 'MBL', 'deskripsi' => 'Meja, kursi, lemari, papan tulis']);
        $catElektronik = Category::create(['unit_id' => null, 'nama' => 'Elektronik & IT', 'kode' => 'ELEK', 'deskripsi' => 'Komputer, laptop, proyektor, AC, TV']);
        $catLab        = Category::create(['unit_id' => null, 'nama' => 'Alat Laboratorium & Komputer', 'kode' => 'LAB', 'deskripsi' => 'Perangkat praktikum & jaringan komputer']);
        $catPraktikum  = Category::create(['unit_id' => null, 'nama' => 'Peralatan Bengkel & Praktikum', 'kode' => 'BKL', 'deskripsi' => 'Toolset, trainer kit, perangkat kejuruan']);
        $catOlahraga   = Category::create(['unit_id' => null, 'nama' => 'Sarana Olahraga', 'kode' => 'OLG', 'deskripsi' => 'Bola, matras, meja pingpong']);

        /* ── 4. Merek / Brand ────────────────────────────────── */
        $mChitose  = Merek::create(['unit_id' => null, 'nama' => 'Chitose', 'kode' => 'CHT', 'deskripsi' => 'Produsen meubeler sekolah']);
        $mFutura   = Merek::create(['unit_id' => null, 'nama' => 'Futura', 'kode' => 'FTR', 'deskripsi' => 'Kursi & meja ergonomis']);
        $mEpson    = Merek::create(['unit_id' => null, 'nama' => 'Epson', 'kode' => 'EPS', 'deskripsi' => 'Proyektor & printer']);
        $mAsus     = Merek::create(['unit_id' => null, 'nama' => 'Asus', 'kode' => 'ASU', 'deskripsi' => 'Laptop & komputer']);
        $mSharp    = Merek::create(['unit_id' => null, 'nama' => 'Sharp', 'kode' => 'SRP', 'deskripsi' => 'Elektronik & pendingin ruangan']);
        $mMikrotik = Merek::create(['unit_id' => null, 'nama' => 'MikroTik', 'kode' => 'MTK', 'deskripsi' => 'Perangkat jaringan']);

        /* ── 5. Lokasi / Ruangan ─────────────────────────────── */
        // MI: Ruang Kelas 1 - 6 + Ruang Guru
        $locMI = [];
        foreach (range(1, 6) as $i) {
            $locMI["kelas-{$i}"] = Location::create([
                'unit_id' => $mi->id,
                'nama'    => "Ruang Kelas {$i} MI",
                'slug'    => "mi-kelas-{$i}",
                'tipe'    => 'kelas',
            ]);
        }
        $locMI['ruang-guru'] = Location::create(['unit_id' => $mi->id, 'nama' => 'Ruang Guru MI', 'slug' => 'mi-ruang-guru', 'tipe' => 'kantor']);

        // MTS: Ruang Kelas 8 & 9, Laboratorium Komputer, Aula, Ruang Guru
        $locMTS = [];
        foreach ([8, 9] as $i) {
            $locMTS["kelas-{$i}"] = Location::create([
                'unit_id' => $mts->id,
                'nama'    => "Ruang Kelas {$i} MTS",
                'slug'    => "mts-kelas-{$i}",
                'tipe'    => 'kelas',
            ]);
        }
        $locMTS['lab-komputer'] = Location::create(['unit_id' => $mts->id, 'nama' => 'Laboratorium Komputer MTS', 'slug' => 'mts-lab-komputer', 'tipe' => 'lab']);
        $locMTS['aula']         = Location::create(['unit_id' => $mts->id, 'nama' => 'Aula MTS', 'slug' => 'mts-aula', 'tipe' => 'aula']);
        $locMTS['ruang-guru']   = Location::create(['unit_id' => $mts->id, 'nama' => 'Ruang Guru MTS', 'slug' => 'mts-ruang-guru', 'tipe' => 'kantor']);

        // SMK: Ruang Kelas 10 - 12, Laboratorium Komputer, Aula, Ruang Guru
        $locSMK = [];
        foreach ([10, 11, 12] as $i) {
            $locSMK["kelas-{$i}"] = Location::create([
                'unit_id' => $smk->id,
                'nama'    => "Ruang Kelas {$i} SMK",
                'slug'    => "smk-kelas-{$i}",
                'tipe'    => 'kelas',
            ]);
        }
        $locSMK['lab-komputer'] = Location::create(['unit_id' => $smk->id, 'nama' => 'Laboratorium Komputer SMK', 'slug' => 'smk-lab-komputer', 'tipe' => 'lab']);
        $locSMK['aula']         = Location::create(['unit_id' => $smk->id, 'nama' => 'Aula SMK', 'slug' => 'smk-aula', 'tipe' => 'aula']);
        $locSMK['ruang-guru']   = Location::create(['unit_id' => $smk->id, 'nama' => 'Ruang Guru SMK', 'slug' => 'smk-ruang-guru', 'tipe' => 'kantor']);

        /* ── 6. Data Barang / Aset ───────────────────────────── */
        $aMeja = $this->asset([
            'unit_id'     => $mi->id,
            'location_id' => $locMI['kelas-1']->id,
            'category_id' => $catMeubel->id,
            'merek_id'    => $mChitose->id,
            'kode_barang' => 'MI-K1-MJ50',
            'nama_barang' => 'Meja Belajar Siswa MI',
            'baik'        => 45,
            'ringan'      => 3,
            'berat'       => 2,
            'foto'        => 'https://images.unsplash.com/photo-1580481072645-022f9a6d1270?w=600&q=80',
            'spesifikasi' => 'Kayu jati lapis HPL, ukuran 60x50 cm',
            'nilai'       => 15000000,
        ]);

        $aKursi = $this->asset([
            'unit_id'     => $mi->id,
            'location_id' => $locMI['kelas-1']->id,
            'category_id' => $catMeubel->id,
            'merek_id'    => $mFutura->id,
            'kode_barang' => 'MI-K1-KS50',
            'nama_barang' => 'Kursi Belajar Siswa MI',
            'baik'        => 48,
            'ringan'      => 2,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1503602642458-232111445657?w=600&q=80',
            'spesifikasi' => 'Rangka besi pipa, sandaran kayu',
            'nilai'       => 10000000,
        ]);

        $aMejaMts = $this->asset([
            'unit_id'     => $mts->id,
            'location_id' => $locMTS['kelas-8']->id,
            'category_id' => $catMeubel->id,
            'merek_id'    => $mChitose->id,
            'kode_barang' => 'MTS-K8-MJ40',
            'nama_barang' => 'Meja Siswa MTS Ergonomis',
            'baik'        => 36,
            'ringan'      => 3,
            'berat'       => 1,
            'foto'        => 'https://images.unsplash.com/photo-1580481072645-022f9a6d1270?w=600&q=80',
            'spesifikasi' => 'Rangka besi hollow 2x4 cm',
            'nilai'       => 16000000,
        ]);

        $aPcMts = $this->asset([
            'unit_id'     => $mts->id,
            'location_id' => $locMTS['lab-komputer']->id,
            'category_id' => $catLab->id,
            'merek_id'    => $mAsus->id,
            'kode_barang' => 'MTS-LAB-PC20',
            'nama_barang' => 'PC Lab Komputer MTS',
            'baik'        => 18,
            'ringan'      => 2,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?w=600&q=80',
            'spesifikasi' => 'Core i5, RAM 8GB, SSD 256GB, monitor 21 inci',
            'nilai'       => 140000000,
        ]);

        $aProyektorMts = $this->asset([
            'unit_id'     => $mts->id,
            'location_id' => $locMTS['aula']->id,
            'category_id' => $catElektronik->id,
            'merek_id'    => $mEpson->id,
            'kode_barang' => 'MTS-AULA-PRJ2',
            'nama_barang' => 'Proyektor Aula MTS',
            'baik'        => 2,
            'ringan'      => 0,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&q=80',
            'spesifikasi' => '3600 lumens, HDMI & VGA',
            'nilai'       => 13000000,
        ]);

        $aRouterSmk = $this->asset([
            'unit_id'     => $smk->id,
            'location_id' => $locSMK['lab-komputer']->id,
            'category_id' => $catLab->id,
            'merek_id'    => $mMikrotik->id,
            'kode_barang' => 'SMK-LAB-RB30',
            'nama_barang' => 'RouterBOARD MikroTik RB951Ui',
            'baik'        => 27,
            'ringan'      => 2,
            'berat'       => 1,
            'foto'        => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=600&q=80',
            'spesifikasi' => '5 port ethernet, wireless 2.4GHz, port USB',
            'nilai'       => 24000000,
        ]);

        $aProyektorSmk = $this->asset([
            'unit_id'     => $smk->id,
            'location_id' => $locSMK['aula']->id,
            'category_id' => $catElektronik->id,
            'merek_id'    => $mEpson->id,
            'kode_barang' => 'SMK-AULA-PRJ1',
            'nama_barang' => 'Proyektor High Lumens Aula',
            'baik'        => 2,
            'ringan'      => 0,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&q=80',
            'spesifikasi' => '4200 lumens, wireless screen mirroring',
            'nilai'       => 22000000,
        ]);

        $aLemariGuru = $this->asset([
            'unit_id'     => $smk->id,
            'location_id' => $locSMK['ruang-guru']->id,
            'category_id' => $catMeubel->id,
            'merek_id'    => $mChitose->id,
            'kode_barang' => 'SMK-GUR-LM06',
            'nama_barang' => 'Lemari Arsip Ruang Guru',
            'baik'        => 5,
            'ringan'      => 1,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=600&q=80',
            'spesifikasi' => 'Besi plat 0,7 mm, 4 pintu sliding',
            'nilai'       => 18000000,
        ]);

        $aBolaMi = $this->asset([
            'unit_id'     => $mi->id,
            'location_id' => $locMI['kelas-6']->id,
            'category_id' => $catOlahraga->id,
            'merek_id'    => $mSharp->id,
            'kode_barang' => 'MI-K6-OLG12',
            'nama_barang' => 'Perlengkapan Olahraga MI',
            'baik'        => 10,
            'ringan'      => 2,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1552667466-07770ae110d0?w=600&q=80',
            'spesifikasi' => 'Bola sepak, bola voli, dan matras senam',
            'nilai'       => 4500000,
        ]);

        $aToolsetSmk = $this->asset([
            'unit_id'     => $smk->id,
            'location_id' => $locSMK['kelas-11']->id,
            'category_id' => $catPraktikum->id,
            'merek_id'    => $mAsus->id,
            'kode_barang' => 'SMK-K11-TLS8',
            'nama_barang' => 'Toolset Praktikum Kejuruan',
            'baik'        => 8,
            'ringan'      => 0,
            'berat'       => 0,
            'foto'        => 'https://images.unsplash.com/photo-1581147036324-c1c9c4b0d92d?w=600&q=80',
            'spesifikasi' => 'Tang crimping, LAN tester, obeng set',
            'nilai'       => 6000000,
        ]);

        /* ── 7. Mutasi Barang Masuk (pengadaan awal) ─────────── */
        $this->mutasiMasuk($aMeja, $miUser, 50, 'Pengadaan awal semester 50 meja siswa');
        $this->mutasiMasuk($aKursi, $miUser, 50, 'Pengadaan awal semester 50 kursi siswa');
        $this->mutasiMasuk($aPcMts, $mtsUser, 20, 'Pengadaan komputer Laboratorium Komputer MTS');
        $this->mutasiMasuk($aRouterSmk, $smkUser, 30, 'Pengadaan perangkat jaringan Lab Komputer SMK');
        $this->mutasiMasuk($aProyektorSmk, $smkUser, 2, 'Pengadaan proyektor Aula SMK');

        /* ── 8. Laporan Kerusakan + Penggantian + Peminjaman ─── */
        $lapMi = Report::create([
            'unit_id'     => $mi->id,
            'asset_id'    => $aMeja->id,
            'location_id' => $aMeja->location_id,
            'user_id'     => $miUser->id,
            'jenis'       => 'kerusakan',
            'judul'       => 'Kerusakan Kaki Meja Belajar Siswa Kelas 1',
            'deskripsi'   => 'Sebanyak 3 meja mengalami engsel penyangga kendor dan 2 meja patah pada papan kayu.',
            'qty'         => 5,
            'status'      => 'pending',
            'verifikasi'  => null,
        ]);

        $lapSmk = Report::create([
            'unit_id'     => $smk->id,
            'asset_id'    => $aRouterSmk->id,
            'location_id' => $aRouterSmk->location_id,
            'user_id'     => $smkUser->id,
            'jenis'       => 'kerusakan',
            'judul'       => 'Gangguan Adaptor Power Router Lab Komputer',
            'deskripsi'   => 'Dua unit router tidak menyala karena adaptor power rusak saat praktikum jaringan.',
            'qty'         => 2,
            'status'      => 'proses',
            'verifikasi'  => null,
            'tanggapan'   => 'Sedang dijadwalkan pembelian adaptor pengganti oleh bagian sarpras Yayasan.',
        ]);

        $lapMts = Report::create([
            'unit_id'     => $mts->id,
            'asset_id'    => $aPcMts->id,
            'location_id' => $aPcMts->location_id,
            'user_id'     => $mtsUser->id,
            'jenis'       => 'kerusakan',
            'judul'       => 'Penggantian RAM PC Lab Komputer',
            'deskripsi'   => 'Dua unit PC sering restart sendiri, hasil pemeriksaan menunjukkan modul RAM rusak.',
            'qty'         => 2,
            'status'      => 'selesai',
            'verifikasi'  => 'disetujui',
            'verified_by' => $yayasanUser->id,
            'verified_at' => now()->subDays(2),
            'tanggapan'   => 'Disetujui. RAM pengganti telah dibelikan Yayasan dan sudah dipasang teknisi sekolah.',
        ]);

        // Barang keluar: penggantian unit rusak berat meja MI
        $lapGantiMi = Report::create([
            'unit_id'     => $mi->id,
            'asset_id'    => $aMeja->id,
            'location_id' => $aMeja->location_id,
            'user_id'     => $miUser->id,
            'jenis'       => 'penggantian',
            'judul'       => 'Pengajuan Penggantian 1 Unit Meja Belajar Siswa MI',
            'deskripsi'   => 'Satu unit meja berkondisi rusak berat dikeluarkan dari Ruang Kelas 1 MI '
                             . 'dan diajukan untuk diganti. Alasan: papan meja pecah dan tidak dapat diperbaiki.',
            'qty'         => 1,
            'status'      => 'pending',
            'verifikasi'  => null,
        ]);

        AssetMutation::create([
            'asset_id'       => $aMeja->id,
            'unit_id'        => $mi->id,
            'location_id'    => $aMeja->location_id,
            'tipe'           => 'keluar',
            'jenis'          => 'penggantian',
            'kondisi_sumber' => 'rusak_berat',
            'qty'            => 1,
            'tanggal_keluar' => now()->subDays(3)->toDateString(),
            'keterangan'     => 'Papan meja pecah dan tidak dapat diperbaiki, diajukan penggantian.',
            'user_id'        => $miUser->id,
            'report_id'      => $lapGantiMi->id,
        ]);

        // Stok kondisi rusak berat berkurang mengikuti barang yang keluar.
        $aMeja->kondisi_rusak_berat = max(0, $aMeja->kondisi_rusak_berat - 1);
        $aMeja->total_qty = $aMeja->kondisi_baik + $aMeja->kondisi_rusak_ringan + $aMeja->kondisi_rusak_berat;
        $aMeja->save();

        // Barang keluar: peminjaman proyektor Aula SMK (masih dipinjam)
        $lapPinjamSmk = Report::create([
            'unit_id'     => $smk->id,
            'asset_id'    => $aProyektorSmk->id,
            'location_id' => $aProyektorSmk->location_id,
            'user_id'     => $smkUser->id,
            'jenis'       => 'peminjaman',
            'judul'       => 'Peminjaman 1 Unit Proyektor High Lumens Aula',
            'deskripsi'   => 'Satu unit proyektor dipinjamkan kepada Panitia Kegiatan Pramuka '
                             . 'untuk kegiatan perkemahan sekolah.',
            'qty'         => 1,
            'status'      => 'pending',
            'verifikasi'  => null,
        ]);

        AssetMutation::create([
            'asset_id'                => $aProyektorSmk->id,
            'unit_id'                 => $smk->id,
            'location_id'             => $aProyektorSmk->location_id,
            'tipe'                    => 'keluar',
            'jenis'                   => 'peminjaman',
            'kondisi_sumber'          => 'baik',
            'qty'                     => 1,
            'peminjam'                => 'Panitia Kegiatan Pramuka SMK',
            'kontak_peminjam'         => '0812-3456-7890',
            'tanggal_keluar'          => now()->subDays(2)->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(3)->toDateString(),
            'status_pinjam'           => 'dipinjam',
            'keterangan'              => 'Dipinjam untuk kegiatan perkemahan sekolah.',
            'user_id'                 => $smkUser->id,
            'report_id'               => $lapPinjamSmk->id,
        ]);

        // Barang keluar: peminjaman kursi MI yang sudah dikembalikan
        $lapPinjamMi = Report::create([
            'unit_id'     => $mi->id,
            'asset_id'    => $aKursi->id,
            'location_id' => $aKursi->location_id,
            'user_id'     => $miUser->id,
            'jenis'       => 'peminjaman',
            'judul'       => 'Peminjaman 10 Unit Kursi Belajar Siswa MI',
            'deskripsi'   => 'Sepuluh unit kursi dipinjamkan untuk acara pengajian wali murid di aula yayasan.',
            'qty'         => 10,
            'status'      => 'selesai',
            'verifikasi'  => 'disetujui',
            'verified_by' => $yayasanUser->id,
            'verified_at' => now()->subDays(5),
            'tanggapan'   => 'Disetujui. Kursi telah dikembalikan lengkap dalam kondisi baik.',
        ]);

        AssetMutation::create([
            'asset_id'                => $aKursi->id,
            'unit_id'                 => $mi->id,
            'location_id'             => $aKursi->location_id,
            'tipe'                    => 'keluar',
            'jenis'                   => 'peminjaman',
            'kondisi_sumber'          => 'baik',
            'qty'                     => 10,
            'peminjam'                => 'Panitia Pengajian Wali Murid',
            'kontak_peminjam'         => '0857-1122-3344',
            'tanggal_keluar'          => now()->subDays(8)->toDateString(),
            'tanggal_kembali_rencana' => now()->subDays(6)->toDateString(),
            'tanggal_kembali_aktual'  => now()->subDays(6)->toDateString(),
            'status_pinjam'           => 'dikembalikan',
            'keterangan'              => 'Acara pengajian wali murid | Dikembalikan lengkap kondisi baik.',
            'user_id'                 => $miUser->id,
            'report_id'               => $lapPinjamMi->id,
        ]);

        AssetMutation::create([
            'asset_id'       => $aKursi->id,
            'unit_id'        => $mi->id,
            'location_id'    => $aKursi->location_id,
            'tipe'           => 'masuk',
            'jenis'          => 'pengembalian',
            'kondisi_sumber' => 'baik',
            'qty'            => 10,
            'peminjam'       => 'Panitia Pengajian Wali Murid',
            'tanggal_keluar' => now()->subDays(6)->toDateString(),
            'keterangan'     => 'Pengembalian barang pinjaman oleh Panitia Pengajian Wali Murid (kondisi: baik)',
            'user_id'        => $miUser->id,
            'report_id'      => $lapPinjamMi->id,
        ]);

        /* ── 9. Notifikasi awal ──────────────────────────────── */
        // Laporan yang belum diverifikasi -> notifikasi untuk Admin Yayasan.
        foreach ([$lapMi, $lapGantiMi, $lapPinjamSmk] as $laporan) {
            Notification::create([
                'user_id'   => $yayasanUser->id,
                'unit_id'   => $laporan->unit_id,
                'report_id' => $laporan->id,
                'asset_id'  => $laporan->asset_id,
                'tipe'      => 'laporan_baru',
                'judul'     => '[' . $laporan->unit->label() . '] ' . $laporan->jenisLabel() . ' baru',
                'pesan'     => $laporan->judul . ' - menunggu verifikasi Admin Yayasan.',
                'url'       => '/laporan?status=pending',
                'icon'      => 'file-plus',
            ]);
        }

        // Hasil verifikasi Yayasan -> notifikasi untuk Admin Unit terkait.
        Notification::create([
            'user_id'   => $mtsUser->id,
            'unit_id'   => $mts->id,
            'report_id' => $lapMts->id,
            'asset_id'  => $lapMts->asset_id,
            'tipe'      => 'laporan_disetujui',
            'judul'     => 'Laporan Anda telah disetujui Yayasan',
            'pesan'     => $lapMts->judul . ' - status: Selesai - Disetujui. Tanggapan: ' . $lapMts->tanggapan,
            'url'       => '/laporan/selesai',
            'read_at'   => null,
        ]);

        Notification::create([
            'user_id'   => $smkUser->id,
            'unit_id'   => $smk->id,
            'report_id' => $lapSmk->id,
            'asset_id'  => $lapSmk->asset_id,
            'tipe'      => 'laporan_proses',
            'judul'     => 'Laporan Anda sedang diproses Yayasan',
            'pesan'     => $lapSmk->judul . ' - status: Sedang Diproses Yayasan.',
            'url'       => '/laporan',
            'read_at'   => null,
        ]);

        Notification::create([
            'user_id'   => $miUser->id,
            'unit_id'   => $mi->id,
            'report_id' => $lapPinjamMi->id,
            'asset_id'  => $lapPinjamMi->asset_id,
            'tipe'      => 'laporan_disetujui',
            'judul'     => 'Laporan Anda telah disetujui Yayasan',
            'pesan'     => $lapPinjamMi->judul . ' - status: Selesai - Disetujui.',
            'url'       => '/laporan/selesai',
            'read_at'   => now()->subDay(),
        ]);
    }

    /**
     * Membuat data aset sekaligus menjaga kontrak kondisi & QR Code.
     *
     * @param  array<string, mixed>  $data
     */
    private function asset(array $data): Asset
    {
        $total = (int) $data['baik'] + (int) $data['ringan'] + (int) $data['berat'];

        return Asset::create([
            'unit_id'              => $data['unit_id'],
            'location_id'          => $data['location_id'],
            'category_id'          => $data['category_id'],
            'merek_id'             => $data['merek_id'],
            'kode_barang'          => $data['kode_barang'],
            'nama_barang'          => $data['nama_barang'],
            'total_qty'            => $total,
            'kondisi_baik'         => (int) $data['baik'],
            'kondisi_rusak_ringan' => (int) $data['ringan'],
            'kondisi_rusak_berat'  => (int) $data['berat'],
            'foto_barang'          => $data['foto'] ?? null,
            'qr_code_path'         => QrCodeHelper::generateSvg($data['kode_barang']),
            'spesifikasi'          => $data['spesifikasi'] ?? null,
            'nilai_estimasi'       => $data['nilai'] ?? 0,
        ]);
    }

    private function mutasiMasuk(Asset $asset, User $user, int $qty, string $keterangan): void
    {
        AssetMutation::create([
            'asset_id'       => $asset->id,
            'unit_id'        => $asset->unit_id,
            'location_id'    => $asset->location_id,
            'tipe'           => 'masuk',
            'jenis'          => 'pengadaan',
            'kondisi_sumber' => 'baik',
            'qty'            => $qty,
            'tanggal_keluar' => now()->subMonth()->toDateString(),
            'keterangan'     => $keterangan,
            'user_id'        => $user->id,
        ]);
    }
}
