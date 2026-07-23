<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ];

        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        $request->session()->forget('password_prompt_dismissed');

        return back()->with('password_changed', 'Password berhasil diubah.');
    }

    public function dismissPrompt(Request $request): RedirectResponse
    {
        $request->session()->put('password_prompt_dismissed', true);

        return back();
    }
}
