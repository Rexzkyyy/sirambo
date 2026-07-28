<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Tampilkan semua user dengan pencarian dan pagination
    public function index(Request $request)
    {
        // Ambil parameter pencarian
        $search = $request->input('search');
        
        // Query dengan eager loading wilayah dan relasinya
        $query = User::with(['wilayah.provinsi', 'wilayah.kabupaten']);
        
        // Jika ada pencarian
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                  ->orWhere('users.email', 'like', '%' . $search . '%')
                  ->orWhere('users.role', 'like', '%' . $search . '%')
                  // Pencarian berdasarkan nama provinsi
                  ->orWhereHas('wilayah.provinsi', function($q) use ($search) {
                      $q->where('nama_provinsi', 'like', '%' . $search . '%');
                  })
                  // Pencarian berdasarkan nama kabupaten
                  ->orWhereHas('wilayah.kabupaten', function($q) use ($search) {
                      $q->where('nama_kabupaten', 'like', '%' . $search . '%');
                  })
                  // Pencarian berdasarkan tipe wilayah
                  ->orWhereHas('wilayah', function($q) use ($search) {
                      $q->where('tipe', 'like', '%' . $search . '%');
                  });
            });
        }
        
        // Urutkan dan paginate
        $users = $query->orderBy('users.id', 'desc')->paginate(10);
        
        // Jika request AJAX untuk pagination
        if ($request->ajax()) {
            return view('users.partials.table', compact('users'))->render();
        }
        
        // Kirim data ke view
        return view('users.index', compact('users'));
    }

    // Form tambah user
    public function create()
    {
        // Gunakan scope withNama dari model Wilayah
        $wilayah = Wilayah::withNama()
            ->with(['provinsi', 'kabupaten'])
            ->orderByRaw("
                CASE 
                    WHEN wilayah.tipe = 'provinsi' THEN 1
                    WHEN wilayah.tipe = 'kota' THEN 2
                    WHEN wilayah.tipe = 'kabupaten' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('nama_wilayah')
            ->get();
            
        return view('users.create', compact('wilayah'));
    }

    // Form edit user
    public function edit(User $user)
    {
        // Gunakan scope withNama dari model Wilayah
        $wilayah = Wilayah::withNama()
            ->with(['provinsi', 'kabupaten'])
            ->orderByRaw("
                CASE 
                    WHEN wilayah.tipe = 'provinsi' THEN 1
                    WHEN wilayah.tipe = 'kota' THEN 2
                    WHEN wilayah.tipe = 'kabupaten' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('nama_wilayah')
            ->get();
            
        return view('users.create', compact('user', 'wilayah'));
    }

    // Simpan user baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:provinsi,kabupaten,kota',
            'id_wilayah' => [
                'required',
                'exists:wilayah,id_wilayah',
                function ($attribute, $value, $fail) use ($request) {
                    $wilayah = Wilayah::find($value);
                    if ($wilayah && $wilayah->tipe !== $request->role) {
                        $fail('Role user harus sesuai dengan tipe wilayah yang dipilih.');
                    }
                },
            ],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'id_wilayah' => $request->id_wilayah,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan');
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:provinsi,kabupaten,kota',
            'id_wilayah' => [
                'required',
                'exists:wilayah,id_wilayah',
                function ($attribute, $value, $fail) use ($request) {
                    $wilayah = Wilayah::find($value);
                    if ($wilayah && $wilayah->tipe !== $request->role) {
                        $fail('Role user harus sesuai dengan tipe wilayah yang dipilih.');
                    }
                },
            ],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->role = $request->role;
        $user->id_wilayah = $request->id_wilayah;
        $user->save();

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diperbarui');
    }

    // Hapus user
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus');
    }
}