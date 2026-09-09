@extends('layouts.admin')

@section('page-title', 'Master Data Merek / Brand')
@section('page-icon', 'bookmark')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="bookmark"></i> Master Data Merek / Brand</h1>
        <p>Daftar merek produsen barang inventaris yang dipakai saat mendata aset</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openTambah()">
            <i data-lucide="plus-circle"></i> Tambah Merek
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Daftar Merek</h3>
        <span class="muted">{{ $mereks->count() }} merek</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama Merek</th>
                    <th>Kode</th>
                    <th>Deskripsi</th>
                    <th style="text-align:center;">Jenis Barang</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mereks as $i => $merek)
                    @php $stat = $jumlahAset[$merek->id] ?? null; @endphp
                    <tr>
                        <td style="color:var(--gray);font-weight:700;">{{ $i + 1 }}</td>
                        <td style="font-weight:700;">{{ $merek->nama }}</td>
                        <td><span class="code">{{ $merek->kode ?? '-' }}</span></td>
                        <td style="font-size:.82rem;color:var(--gray);">{{ $merek->deskripsi ?? '-' }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $stat->total ?? 0 }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-blue">{{ number_format((int) ($stat->qty ?? 0)) }} unit</span>
                        </td>
                        <td>
                            <div class="cell-actions">
                                <button type="button" class="btn btn-blue btn-sm"
                                    onclick="openUbah({{ $merek->id }}, @js($merek->nama), @js($merek->kode), @js($merek->deskripsi))">
                                    <i data-lucide="pencil" style="width:13px;height:13px;"></i> Ubah
                                </button>
                                <form action="{{ route('merek.destroy', $merek->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus merek {{ addslashes($merek->nama) }}?')">
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
                        <td colspan="7">
                            <div class="empty-state">
                                <i data-lucide="bookmark" style="width:36px;height:36px;stroke-width:1.4;"></i>
                                <strong>Belum ada merek terdaftar</strong>
                                <p>Tambahkan merek seperti Chitose, Epson, Asus, atau MikroTik.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ MODAL ══ --}}
<div class="modal" id="merekModal">
    <div class="modal-box">
        <form id="merekForm" method="POST" action="{{ route('merek.store') }}">
            @csrf
            <input type="hidden" name="_method" id="merekMethod" value="POST">

            <div class="modal-head">
                <h3 id="merekTitle">Tambah Merek</h3>
                <button type="button" class="modal-close" onclick="closeModal('merekModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="field">
                    <label for="merekNama">Nama merek <span class="req">*</span></label>
                    <input type="text" id="merekNama" name="nama" class="input" required maxlength="255"
                           placeholder="Contoh: Epson">
                </div>
                <div class="field">
                    <label for="merekKode">Kode singkat</label>
                    <input type="text" id="merekKode" name="kode" class="input" maxlength="20"
                           placeholder="Otomatis bila dikosongkan" style="text-transform:uppercase;">
                </div>
                <div class="field">
                    <label for="merekDesk">Deskripsi</label>
                    <textarea id="merekDesk" name="deskripsi" class="textarea" rows="3" maxlength="500"
                              placeholder="Contoh: Proyektor & printer"></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('merekModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const STORE_URL = @json(route('merek.store'));
    const BASE_URL  = @json(url('merek'));

    function openTambah() {
        document.getElementById('merekTitle').textContent = 'Tambah Merek';
        document.getElementById('merekForm').action = STORE_URL;
        document.getElementById('merekMethod').value = 'POST';
        document.getElementById('merekNama').value = '';
        document.getElementById('merekKode').value = '';
        document.getElementById('merekDesk').value = '';
        openModal('merekModal');
    }

    function openUbah(id, nama, kode, deskripsi) {
        document.getElementById('merekTitle').textContent = 'Ubah Merek';
        document.getElementById('merekForm').action = BASE_URL + '/' + id;
        document.getElementById('merekMethod').value = 'PUT';
        document.getElementById('merekNama').value = nama || '';
        document.getElementById('merekKode').value = kode || '';
        document.getElementById('merekDesk').value = deskripsi || '';
        openModal('merekModal');
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', openTambah);
    @endif
</script>
@endsection
