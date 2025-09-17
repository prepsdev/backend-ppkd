<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $apiResponse = Http::post('https://api-stara.bpkp.go.id/api/auth/login', [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        if ($apiResponse->successful()) {
            $data = $apiResponse->json('data.user_info');

            session(['username' => $data['username']]);
            session(['nama_gelar' => $data['nama_gelar'] ?? '']);
            session(['namaunit' => $data['namaunit'] ?? '']);
            session(['name' => $data['name'] ?? '']);

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil!',
                'user' => [
                    'username' => $data['username'],
                    'nama_gelar' => $data['nama_gelar'] ?? '',
                    'namaunit' => $data['namaunit'] ?? '',
                    'name' => $data['name'] ?? '',
                ]
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Username/password salah!'
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $username = session('username');

        session()->forget('username');
        session()->forget('nama_gelar');
        session()->forget('namaunit');
        session()->forget('name');

        return response()->json([
            'success' => true,
            'message' => 'You have been logged out'
        ], 200);
    }
}
