@extends('layouts.admin')

@section('page-title', 'Master Data Lokasi Ruangan')
@section('page-icon', 'map-pin')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="map-pin"></i> Master Data Lokasi Ruangan</h1>
        <p>Ruangan yang terdaftar di sini otomatis muncul pada menu <strong>Ruang &amp; Fasilitas</strong> di sidebar</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openTambah()">
            <i data-lucide="plus-circle"></i> Tambah Ruangan
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Daftar Ruangan</h3>
        <span class="muted">{{ $locations->count() }} ruangan</span>
    </div>
    <div class="scroll-hint"><i data-lucide="move-horizontal" style="width:12px;height:12px;"></i> Geser tabel ke samping untuk melihat kolom lain</div>
    <div class="table-wrap">
        <table class="table table-wide">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama Ruangan</th>
                    <th>Tipe</th>
                    <th>Kode / Slug</th>
                    <th>Deskripsi</th>
                    <th style="text-align:center;">Jenis Barang</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Perlu Perbaikan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locations as $i => $loc)
                    @php $stat = $rekap[$loc->id] ?? null; @endphp
                    <tr>
                        <td style="color:var(--gray);font-weight:700;">{{ $i + 1 }}</td>
                        <td style="font-weight:700;">
                            <a href="{{ route('ruangan.show', ['ruangan' => $loc->slug]) }}" style="color:var(--navy);text-decoration:none;">
                                {{ $loc->nama }}
                            </a>
                        </td>
                        <td><span class="badge badge-gray">{{ strtoupper($loc->tipe) }}</span></td>
                        <td><span class="code">{{ $loc->slug }}</span></td>
                        <td style="font-size:.82rem;color:var(--gray);">{{ $loc->deskripsi ?? '-' }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $loc->assets_count }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-blue">{{ number_format((int) ($stat->qty ?? 0)) }} unit</span>
                        </td>
                        <td style="text-align:center;">
                            @if((int) ($stat->rusak ?? 0) > 0)
                                <span class="badge badge-amber">{{ (int) $stat->rusak }} unit</span>
                            @else
                                <span class="badge badge-green">Aman</span>
                            @endif
                        </td>
                        <td>
                            <div class="cell-actions">
                                <a href="{{ route('ruangan.show', ['ruangan' => $loc->slug]) }}" class="btn btn-ghost btn-sm">
                                    <i data-lucide="eye" style="width:13px;height:13px;"></i>
                                </a>
                                <button type="button" class="btn btn-blue btn-sm"
                                    onclick="openUbah({{ $loc->id }}, @js($loc->nama), @js($loc->tipe), @js($loc->deskripsi))">
                                    <i data-lucide="pencil" style="width:13px;height:13px;"></i> Ubah
                                </button>
                                <form action="{{ route('locations.destroy', $loc->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus ruangan {{ addslashes($loc->nama) }}? Ruangan hanya bisa dihapus jika sudah tidak memuat barang.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-red btn-sm">
                                        <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i data-lucide="map-pin-off" style="width:36px;height:36px;stroke-width:1.4;"></i>
                                <strong>Belum ada ruangan terdaftar</strong>
                                <p>Tambahkan ruang kelas, laboratorium, aula, atau ruang guru untuk mulai mendata aset.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ MODAL ══ --}}
<div class="modal" id="locModal">
    <div class="modal-box">
        <form id="locForm" method="POST" action="{{ route('locations.store') }}">
            @csrf
            <input type="hidden" name="_method" id="locMethod" value="POST">

            <div class="modal-head">
                <h3 id="locTitle">Tambah Ruangan</h3>
                <button type="button" class="modal-close" onclick="closeModal('locModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="field">
                    <label for="locNama">Nama ruangan <span class="req">*</span></label>
                    <input type="text" id="locNama" name="nama" class="input" required maxlength="255"
                           placeholder="Contoh: Laboratorium Komputer">
                    <span class="hint">Nama ruangan harus unik dalam satu unit sekolah.</span>
                </div>
                <div class="field">
                    <label for="locTipe">Tipe ruangan <span class="req">*</span></label>
                    <select id="locTipe" name="tipe" class="select" required>
                        @foreach($tipeList as $tipe)
                            <option value="{{ $tipe }}">{{ ucfirst($tipe) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="locDesk">Deskripsi</label>
                    <textarea id="locDesk" name="deskripsi" class="textarea" rows="3" maxlength="500"
                              placeholder="Keterangan tambahan ruangan…"></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('locModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const STORE_URL = @json(route('locations.store'));
    const BASE_URL  = @json(url('locations'));

    function openTambah() {
        document.getElementById('locTitle').textContent = 'Tambah Ruangan';
        document.getElementById('locForm').action = STORE_URL;
        document.getElementById('locMethod').value = 'POST';
        document.getElementById('locNama').value = '';
        document.getElementById('locTipe').value = 'kelas';
        document.getElementById('locDesk').value = '';
        openModal('locModal');
    }

    function openUbah(id, nama, tipe, deskripsi) {
        document.getElementById('locTitle').textContent = 'Ubah Ruangan';
        document.getElementById('locForm').action = BASE_URL + '/' + id;
        document.getElementById('locMethod').value = 'PUT';
        document.getElementById('locNama').value = nama || '';
        document.getElementById('locTipe').value = tipe || 'kelas';
        document.getElementById('locDesk').value = deskripsi || '';
        openModal('locModal');
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', openTambah);
    @endif
</script>
@endsection
