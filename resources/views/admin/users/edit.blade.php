@extends('layouts.admin')

@section('page-title', 'Ubah Akun — ' . $user->name)
@section('page-icon', 'user-cog')

@section('content')

<div class="page-head">
    <div>
        <h1>
            <a href="{{ route('users.index') }}" class="btn btn-ghost btn-icon" title="Kembali"><i data-lucide="arrow-left"></i></a>
            <i data-lucide="user-cog"></i> Ubah Akun Pengguna
        </h1>
        <p>Perbarui detail akun dan/atau ganti sandi login pengguna</p>
    </div>
</div>

<div style="max-width:720px;display:flex;flex-direction:column;gap:18px;">

    {{-- ── IDENTITAS ── --}}
    <div class="card">
        <div class="card-head">
            <h3><i data-lucide="id-card"></i> Identitas Akun</h3>
            <span class="muted">Dibuat {{ $user->created_at?->format('d M Y') }}</span>
        </div>

        <div class="card-pad" style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
            <span style="width:56px;height:56px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800;color:#fff;background:linear-gradient(135deg,#152C48,#2A4F80);">
                {{ $user->initials() }}
            </span>
            <div style="min-width:0;">
                <div style="font-weight:800;font-size:1rem;">{{ $user->name }}</div>
                <div style="font-size:.82rem;color:var(--gray);">{{ $user->email }}</div>
                <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">
                    <span class="badge {{ $user->isSuperAdmin() ? 'badge-purple' : 'badge-blue' }}">{{ $user->roleLabel() }}</span>
                    <span class="badge badge-gray">{{ $user->unit->nama ?? 'Semua Unit (Yayasan)' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FORM UBAH ── --}}
    <div class="card">
        <div class="card-head"><h3><i data-lucide="settings-2"></i> Detail Akun & Sandi</h3></div>

        <form action="{{ route('users.update', $user->id) }}" method="POST"
              class="card-pad" style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Nama lengkap <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="input" required maxlength="255"
                       value="{{ old('name', $user->name) }}">
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="email">Email login <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="input" required maxlength="255"
                       value="{{ old('email', $user->email) }}">
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="role">Role / hak akses <span class="req">*</span></label>
                    <select id="role" name="role" class="select" required onchange="toggleUnit()"
                            @disabled($user->id === auth()->id())>
                        <option value="admin_unit" @selected(old('role', $user->isSuperAdmin() ? 'super_admin' : 'admin_unit') === 'admin_unit')>
                            Admin Unit Sekolah
                        </option>
                        <option value="super_admin" @selected(old('role', $user->isSuperAdmin() ? 'super_admin' : 'admin_unit') === 'super_admin')>
                            Super Admin (Yayasan)
                        </option>
                    </select>
                    @if($user->id === auth()->id())
                        <span class="hint">Role akun Anda sendiri tidak dapat diubah dari halaman ini.</span>
                        <input type="hidden" name="role" value="super_admin">
                    @endif
                    @error('role')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="field" id="unitField">
                    <label for="unit_id">Unit sekolah <span class="req">*</span></label>
                    <select id="unit_id" name="unit_id" class="select">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((int) old('unit_id', $user->unit_id) === $unit->id)>
                                {{ $unit->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div style="background:var(--slate);border:1px solid var(--border);border-radius:12px;padding:16px;">
                <div style="font-size:.9rem;font-weight:800;color:var(--navy);margin-bottom:4px;">
                    <i data-lucide="key-round"></i> Ganti Sandi
                </div>
                <p style="font-size:.77rem;color:var(--gray);margin-bottom:13px;">
                    Biarkan kosong bila sandi tidak ingin diubah. Minimal 8 karakter.
                </p>

                <div class="form-grid">
                    <div class="field">
                        <label for="password">Sandi baru</label>
                        <input type="password" id="password" name="password" class="input" minlength="8"
                               autocomplete="new-password" placeholder="••••••••">
                        @error('password')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Ulangi sandi baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="input" minlength="8"
                               autocomplete="new-password" placeholder="••••••••">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                <a href="{{ route('users.index') }}" class="btn btn-soft">Batal</a>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function toggleUnit() {
        const role   = document.getElementById('role').value;
        const field  = document.getElementById('unitField');
        const select = document.getElementById('unit_id');
        const isUnit = role === 'admin_unit';
        field.style.display = isUnit ? 'flex' : 'none';
        select.disabled = !isUnit;
    }
    document.addEventListener('DOMContentLoaded', toggleUnit);
</script>
@endsection
