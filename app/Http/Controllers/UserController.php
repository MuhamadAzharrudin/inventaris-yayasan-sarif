<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Pengaturan Akun & User (khusus Super Admin Yayasan).
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        $query = User::with('unit')->orderBy('role')->orderBy('name');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        return view('admin.users.index', [
            'users'   => $query->get(),
            'units'   => Unit::orderBy('id')->get(),
            'keyword' => $keyword,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => ['required', 'in:super_admin,admin_unit'],
            'unit_id'  => ['nullable', 'required_if:role,admin_unit', 'exists:units,id'],
        ], [
            'email.unique'        => 'Email tersebut sudah dipakai akun lain.',
            'password.confirmed'  => 'Konfirmasi password tidak sama.',
            'unit_id.required_if' => 'Admin Unit wajib dipasangkan dengan satu unit sekolah.',
        ], [
            'name'     => 'nama lengkap',
            'email'    => 'email login',
            'password' => 'password',
            'role'     => 'role / hak akses',
            'unit_id'  => 'unit sekolah',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'unit_id'  => $validated['role'] === 'super_admin' ? null : $validated['unit_id'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Akun "' . $validated['name'] . '" berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $user = User::with('unit')->findOrFail($id);

        return view('admin.users.edit', [
            'user'  => $user,
            'units' => Unit::orderBy('id')->get(),
        ]);
    }

    /**
     * Ubah detail akun dan/atau sandi.
     *
     * ATURAN VALIDASI:
     *  - Email unik kecuali milik akun yang sedang diubah.
     *  - Password opsional; bila diisi minimal 8 karakter & wajib konfirmasi.
     *  - Role admin_unit wajib memiliki unit sekolah; super_admin selalu null.
     *  - Admin yang sedang login tidak boleh menurunkan role-nya sendiri
     *    menjadi admin_unit (mencegah kehilangan akses Yayasan).
     */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', 'in:super_admin,admin_unit'],
            'unit_id'  => ['nullable', 'required_if:role,admin_unit', 'exists:units,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'email.unique'        => 'Email tersebut sudah dipakai akun lain.',
            'password.confirmed'  => 'Konfirmasi password baru tidak sama.',
            'unit_id.required_if' => 'Admin Unit wajib dipasangkan dengan satu unit sekolah.',
        ], [
            'name'     => 'nama lengkap',
            'email'    => 'email login',
            'role'     => 'role / hak akses',
            'unit_id'  => 'unit sekolah',
            'password' => 'password baru',
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'super_admin') {
            return back()->withInput()->withErrors([
                'role' => 'Anda tidak dapat menurunkan role akun Anda sendiri dari Super Admin Yayasan.',
            ]);
        }

        // Sistem harus selalu menyisakan minimal satu Super Admin Yayasan.
        if ($user->isSuperAdmin() && $validated['role'] !== 'super_admin') {
            $sisaSuperAdmin = User::whereIn('role', ['super_admin', 'admin_yayasan'])
                ->where('id', '<>', $user->id)->count();

            if ($sisaSuperAdmin < 1) {
                return back()->withInput()->withErrors([
                    'role' => 'Tidak dapat mengubah role akun ini karena sistem harus memiliki '
                        . 'minimal satu Super Admin Yayasan.',
                ]);
            }
        }

        $user->name    = $validated['name'];
        $user->email   = $validated['email'];
        $user->role    = $validated['role'];
        $user->unit_id = $validated['role'] === 'super_admin' ? null : $validated['unit_id'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with(
            'success',
            'Akun "' . $user->name . '" berhasil diperbarui'
                . (! empty($validated['password']) ? ' beserta sandi barunya.' : '.')
        );
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        if ($user->isSuperAdmin()) {
            $sisaSuperAdmin = User::whereIn('role', ['super_admin', 'admin_yayasan'])
                ->where('id', '<>', $user->id)->count();

            if ($sisaSuperAdmin < 1) {
                return back()->with('error', 'Akun Super Admin Yayasan terakhir tidak dapat dihapus.');
            }
        }

        $nama = $user->name;
        $user->delete();

        return back()->with('success', 'Akun "' . $nama . '" berhasil dihapus.');
    }
}
