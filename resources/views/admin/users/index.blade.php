@extends('layouts.admin')

@section('page-title', 'Pengaturan Akun & User')
@section('page-icon', 'users')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="users"></i> Pengaturan Akun &amp; User</h1>
        <p>Kelola akun Admin Yayasan dan Admin Unit Sekolah (MI, MTS, SMK) beserta sandinya</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('userModal')">
            <i data-lucide="user-plus"></i> Tambah Akun
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><i data-lucide="list"></i> Daftar Akun Terdaftar</h3>
        <form method="GET" action="{{ route('users.index') }}" style="display:flex;gap:8px;align-items:center;">
            <input type="search" name="q" class="input" value="{{ $keyword }}" placeholder="Cari nama / email…"
                   style="min-width:170px;max-width:250px;">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search"></i></button>
            @if($keyword !== '')
                <a href="{{ route('users.index') }}" class="btn btn-soft btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="scroll-hint"><i data-lucide="move-horizontal" style="width:12px;height:12px;"></i> Geser tabel ke samping untuk melihat kolom lain</div>
    <div class="table-wrap">
        <table class="table table-wide">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama Lengkap</th>
                    <th>Email Login</th>
                    <th>Role / Hak Akses</th>
                    <th>Unit Sekolah</th>
                    <th>Dibuat</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $u)
                    <tr>
                        <td style="color:var(--gray);font-weight:700;">{{ $i + 1 }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px;">
                                <span style="width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;background:linear-gradient(135deg,#152C48,#2A4F80);">
                                    {{ $u->initials() }}
                                </span>
                                <span>
                                    <span style="display:block;font-weight:700;">{{ $u->name }}</span>
                                    @if($u->id === auth()->id())
                                        <span class="badge badge-green" style="margin-top:2px;">Akun Anda</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td><span class="code">{{ $u->email }}</span></td>
                        <td>
                            @if($u->isSuperAdmin())
                                <span class="badge badge-purple">Super Admin Yayasan</span>
                            @else
                                <span class="badge badge-blue">Admin Unit</span>
                            @endif
                        </td>
                        <td style="font-weight:600;font-size:.83rem;">{{ $u->unit->nama ?? 'Semua Unit (Yayasan)' }}</td>
                        <td class="td-nowrap" style="font-size:.8rem;color:var(--gray);">{{ $u->created_at?->format('d/m/Y') }}</td>
                        <td>
                            <div class="cell-actions">
                                <a href="{{ route('users.edit', $u->id) }}" class="btn btn-blue btn-sm">
                                    <i data-lucide="user-cog" style="width:13px;height:13px;"></i> Edit
                                </a>
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-red btn-sm">
                                            <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i data-lucide="user-x" style="width:36px;height:36px;stroke-width:1.4;"></i>
                                <strong>Tidak ada akun yang cocok</strong>
                                <p>Ubah kata kunci pencarian atau tambahkan akun baru.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ MODAL TAMBAH AKUN ══ --}}
<div class="modal" id="userModal">
    <div class="modal-box">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="modal-head">
                <h3>Tambah Akun Admin Baru</h3>
                <button type="button" class="modal-close" onclick="closeModal('userModal')">✕</button>
            </div>

            <div class="modal-body">
                <div class="field">
                    <label for="name">Nama lengkap <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="input" required maxlength="255"
                           value="{{ old('name') }}" placeholder="Contoh: Admin MI Husnul Abror">
                </div>

                <div class="field">
                    <label for="email">Email login <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input" required maxlength="255"
                           value="{{ old('email') }}" placeholder="admin@husnulabror.sch.id">
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password" class="input" required minlength="8"
                               placeholder="Minimal 8 karakter">
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Ulangi password <span class="req">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="input" required minlength="8">
                    </div>
                </div>

                <div class="field">
                    <label for="role">Role / hak akses <span class="req">*</span></label>
                    <select id="role" name="role" class="select" required onchange="toggleUnit()">
                        <option value="admin_unit" @selected(old('role', 'admin_unit') === 'admin_unit')>Admin Unit Sekolah (MI / MTS / SMK)</option>
                        <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin (Yayasan)</option>
                    </select>
                </div>

                <div class="field" id="unitField">
                    <label for="unit_id">Unit sekolah <span class="req">*</span></label>
                    <select id="unit_id" name="unit_id" class="select">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((int) old('unit_id') === $unit->id)>{{ $unit->nama }}</option>
                        @endforeach
                    </select>
                    <span class="hint">Data akun ini akan terisolasi pada unit yang dipilih.</span>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-soft" onclick="closeModal('userModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function toggleUnit() {
        const role = document.getElementById('role').value;
        const field = document.getElementById('unitField');
        const select = document.getElementById('unit_id');
        const isUnit = role === 'admin_unit';
        field.style.display = isUnit ? 'flex' : 'none';
        select.disabled = !isUnit;
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleUnit();
        @if($errors->any())
            openModal('userModal');
        @endif
    });
</script>
@endsection
