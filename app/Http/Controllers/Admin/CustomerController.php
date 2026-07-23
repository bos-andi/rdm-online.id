<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserAccountMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'madrasah_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'jenjang' => ['required', 'in:RA,MI,MTs,MA'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        $plainPassword = $data['password'] ?? Str::password(12, symbols: false);

        $user = User::create([
            'name' => $data['name'],
            'madrasah_name' => $data['madrasah_name'],
            'jenjang' => $data['jenjang'],
            'email' => $data['email'],
            'password' => $plainPassword,
            'must_change_password' => true,
        ]);

        Mail::to($user->email)->send(new UserAccountMail($user, $plainPassword));

        return redirect()
            ->route('admin.pelanggan')
            ->with('customer_created', [
                'email' => $data['email'],
                'password' => $plainPassword,
                'emailed' => true,
            ]);
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $plainPassword = Str::password(12, symbols: false);

        $user->update([
            'password' => $plainPassword,
            'must_change_password' => true,
        ]);

        Mail::to($user->email)->send(new UserAccountMail($user, $plainPassword, isReset: true));

        return redirect()
            ->route('admin.pelanggan')
            ->with('customer_created', [
                'email' => $user->email,
                'password' => $plainPassword,
                'emailed' => true,
                'reset' => true,
            ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'confirm_email' => ['required', 'string'],
        ]);

        if (strtolower($data['confirm_email']) !== strtolower($user->email)) {
            return back()->withErrors(['customer' => 'Konfirmasi email tidak cocok.']);
        }

        $user->loadCount('rdmInstances');

        if ($user->rdm_instances_count > 0) {
            return back()->withErrors([
                'customer' => 'Pelanggan masih memiliki '.$user->rdm_instances_count.' instance RDM. Hapus RDM terlebih dahulu di Manajemen RDM.',
            ]);
        }

        $label = $user->madrasah_name ?? $user->name;
        $email = $user->email;
        $user->delete();

        return redirect()
            ->route('admin.pelanggan')
            ->with('status', 'Akun pelanggan '.$label.' ('.$email.') berhasil dihapus.');
    }
}
