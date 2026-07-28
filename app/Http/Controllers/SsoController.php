<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('bps')->redirect();
    }

    public function callback()
    {
        try {
            $bpsUser = Socialite::driver('bps')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['sso' => 'Gagal login menggunakan SSO BPS.']);
        }

        // Cari user berdasarkan sso_id, nip, atau email
        $user = User::where('sso_id', $bpsUser->id)
                    ->orWhere('nip', $bpsUser->offsetGet('nip'))
                    ->orWhere('email', $bpsUser->email)
                    ->first();

        if ($user) {
            // Update data user jika sudah ada untuk sinkronisasi
            $user->update([
                'sso_id' => $bpsUser->id,
                'nip' => $bpsUser->offsetGet('nip') ?? $user->nip,
                'name' => $bpsUser->name ?? $user->name,
            ]);
        } else {
            // Logika default: Tidak otomatis membuat user jika tidak ditemukan
            // Karena Sirambo biasanya membutuhkan role dan id_wilayah yang spesifik
            return redirect()->route('login')->withErrors(['sso' => 'Akun BPS Anda (' . $bpsUser->email . ') belum terdaftar di aplikasi Sirambo. Silakan hubungi admin.']);
        }

        Auth::login($user);

        request()->session()->regenerate();

        return redirect()->route('pdrb.dashboard');
    }
}
