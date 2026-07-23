<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $email = strtolower($request->string('email')->toString());
        $exists = User::query()->where('email', $email)->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'Email sudah terdaftar. Silakan login untuk deploy RDM berikutnya.',
                'login_url' => route('user.login'),
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Email tersedia — akun akan dibuat otomatis saat deploy.',
        ]);
    }
}
