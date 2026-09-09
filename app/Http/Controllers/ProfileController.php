<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Pengaturan akun milik pengguna yang sedang login.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.akun', ['user' => $request->user()]);
    }

    /**
     * Perbarui nama & email akun sendiri.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.unique' => 'Email tersebut sudah dipakai akun lain.',
        ], [
            'name'  => 'nama lengkap',
            'email' => 'email login',
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Detail akun berhasil diperbarui.');
    }

    /**
     * Ubah sandi akun sendiri.
     *
     * ATURAN VALIDASI:
     *  - Sandi lama wajib benar (current_password).
     *  - Sandi baru minimal 8 karakter, wajib konfirmasi, dan
     *    tidak boleh sama dengan sandi lama.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required'       => 'Sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Sandi saat ini tidak sesuai.',
            'password.confirmed'             => 'Konfirmasi sandi baru tidak sama.',
        ], [
            'password' => 'sandi baru',
        ]);

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Sandi baru tidak boleh sama dengan sandi saat ini.',
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Sandi akun berhasil diperbarui.');
    }
}
