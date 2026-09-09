@extends('layouts.admin')

@section('page-title', 'Master Data Kategori Barang')
@section('page-icon', 'tag')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="tag"></i> Master Data Kategori Barang</h1>
        <p>Kelompok jenis barang sarana & prasarana yang dipakai saat mendata aset</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openTambah()">
            <i data-lucide="plus-circle"></i> Tambah Kategori
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Daftar Kategori</h3>
        <span class="muted">{{ $categories->count() }} kategori</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama Kategori</th>
                    <th>Kode</th>
                    <th>Deskripsi</th>
                    <th style="text-align:center;">Jenis Barang</th>
                    <th style="text-align:center;">Total Unit</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $i => $cat)
                    @php $stat = $jumlahAset[$cat->id] ?? null; @endphp
                    <tr>
                        <td style="color:var(--gray);font-weight:700;">{{ $i + 1 }}</td>
                        <td style="font-weight:700;">{{ $cat->nama }}</td>
                        <td><span class="code">{{ $cat->kode ?? '-' }}</span></td>
                        <td style="font-size:.82rem;color:var(--gray);">{{ $cat->deskripsi ?? '-' }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $stat->total ?? 0 }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-blue">{{ number_format((int) ($stat->qty ?? 0)) }} unit</span>
                        </td>
                        <td>
                            <div class="cell-actions">
                                <button type="button" class="btn btn-blue btn-sm"
                                    onclick="openUbah({{ $cat->id }}, @js($cat->nama), @js($cat->kode), @js($cat->deskripsi))">
                                    <i data-lucide="pencil" style="width:13px;height:13px;"></i> Ubah
                                </button>
                                <form action="{{ route('categories.destroy', $cat->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus kategori {{ addslashes($cat->nama) }}?')">
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
                                <i data-lucide="tags" style="width:36px;height:36px;stroke-width:1.4;"></i>
                                <strong>Belum ada kategori</strong>
                                <p>Tambahkan kategori seperti Meubeler, Elektronik, atau Alat Laboratorium.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ MODAL ══ --}}
<div class="modal" id="catModal">
    <div class="modal-box">
        <form id="catForm" method="POST" action="{{ route('categories.store') }}">
            @csrf
            <input type="hidden" name="_method" id="catMethod" value="POST">

            <div class="modal-head">
                <h3 id="catTitle">Tambah Kategori</h3>
                <button type="button" class="modal-close" onclick="closeModal('catModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="field">
                    <label for="catNama">Nama kategori <span class="req">*</span></label>
                    <input type="text" id="catNama" name="nama" class="input" required maxlength="255"
                           placeholder="Contoh: Elektronik & IT">
                </div>
                <div class="field">
                    <label for="catKode">Kode singkat</label>
                    <input type="text" id="catKode" name="kode" class="input" maxlength="20"
                           placeholder="Otomatis bila dikosongkan" style="text-transform:uppercase;">
                </div>
                <div class="field">
                    <label for="catDesk">Deskripsi</label>
                    <textarea id="catDesk" name="deskripsi" class="textarea" rows="3" maxlength="500"
                              placeholder="Contoh: Komputer, laptop, proyektor, AC, TV"></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('catModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const STORE_URL = @json(route('categories.store'));
    const BASE_URL  = @json(url('categories'));

    function openTambah() {
        document.getElementById('catTitle').textContent = 'Tambah Kategori';
        document.getElementById('catForm').action = STORE_URL;
        document.getElementById('catMethod').value = 'POST';
        document.getElementById('catNama').value = '';
        document.getElementById('catKode').value = '';
        document.getElementById('catDesk').value = '';
        openModal('catModal');
    }

    function openUbah(id, nama, kode, deskripsi) {
        document.getElementById('catTitle').textContent = 'Ubah Kategori';
        document.getElementById('catForm').action = BASE_URL + '/' + id;
        document.getElementById('catMethod').value = 'PUT';
        document.getElementById('catNama').value = nama || '';
        document.getElementById('catKode').value = kode || '';
        document.getElementById('catDesk').value = deskripsi || '';
        openModal('catModal');
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', openTambah);
    @endif
</script>
@endsection
