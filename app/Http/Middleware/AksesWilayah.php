<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AksesWilayah
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Pastikan user login
        if (!$user) {
            abort(403);
        }

        // Jika bukan provinsi, batasi ke kabupaten sendiri
        if ($user->role !== 'provinsi') {
            $request->merge([
                'scope_kabupaten' => $user->id_kabupaten
            ]);
        }

        return $next($request);
    }
}
