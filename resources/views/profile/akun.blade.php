@extends('layouts.admin')

@section('page-title', 'Pengaturan Akun Saya')
@section('page-icon', 'user-circle')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="user-circle"></i> Pengaturan Akun Saya</h1>
        <p>Perbarui detail akun dan sandi login Anda</p>
    </div>
</div>

<div style="max-width:720px;display:flex;flex-direction:column;gap:18px;">

    {{-- ── IDENTITAS ── --}}
    <div class="card">
        <div class="card-head"><h3><i data-lucide="id-card"></i> Identitas Akun</h3></div>
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

    {{-- ── DETAIL AKUN ── --}}
    <div class="card">
        <div class="card-head"><h3><i data-lucide="settings-2"></i> Detail Akun</h3></div>
        <form action="{{ route('profile.update') }}" method="POST" class="card-pad" style="display:flex;flex-direction:column;gap:15px;">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Nama lengkap <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="input" required maxlength="255"
                       value="{{ old('name', $user->name) }}">
            </div>

            <div class="field">
                <label for="email">Email login <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="input" required maxlength="255"
                       value="{{ old('email', $user->email) }}">
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Detail</button>
            </div>
        </form>
    </div>

    {{-- ── GANTI SANDI ── --}}
    <div class="card">
        <div class="card-head"><h3><i data-lucide="key-round"></i> Ganti Sandi</h3></div>
        <form action="{{ route('profile.password') }}" method="POST" class="card-pad" style="display:flex;flex-direction:column;gap:15px;">
            @csrf
            @method('PUT')

            <div class="alert alert-info" style="margin:0;">
                <i data-lucide="info"></i>
                <div>Sandi baru minimal 8 karakter dan tidak boleh sama dengan sandi saat ini.</div>
            </div>

            <div class="field">
                <label for="current_password">Sandi saat ini <span class="req">*</span></label>
                <input type="password" id="current_password" name="current_password" class="input" required
                       autocomplete="current-password">
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="password">Sandi baru <span class="req">*</span></label>
                    <input type="password" id="password" name="password" class="input" required minlength="8"
                           autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirmation">Ulangi sandi baru <span class="req">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="input" required minlength="8"
                           autocomplete="new-password">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-amber"><i data-lucide="key-round"></i> Perbarui Sandi</button>
            </div>
        </form>
    </div>
</div>

@endsection
