@extends('layouts.app')

@section('title', 'Profil Akun')

@section('content')
    <x-user.page-header title="Profil Akun" />

    @if (session('password_changed'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('password_changed') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="app-card p-6 space-y-4">
            <h2 class="font-semibold text-gray-800">Informasi Akun</h2>
            <div>
                <p class="text-sm text-muted">Nama PIC</p>
                <p class="font-medium text-gray-800">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-sm text-muted">Madrasah</p>
                <p class="font-medium text-gray-800">{{ $user->madrasah_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-muted">Email</p>
                <p class="font-medium text-gray-800">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-sm text-muted">Jenjang</p>
                <p class="font-medium text-gray-800">{{ $user->jenjangLabel() }}</p>
            </div>
            <div>
                <p class="text-sm text-muted">Total RDM</p>
                <p class="font-medium text-gray-800">{{ $user->rdmInstances()->count() }} instance</p>
            </div>
            <div class="pt-4 border-t app-divider">
                <form method="POST" action="{{ route('user.logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-rose-50 text-rose-600 hover:bg-rose-100 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <div class="app-card p-6">
            <h2 class="font-semibold text-gray-800 mb-1">Ganti Password</h2>
            @if ($user->must_change_password)
                <p class="text-sm text-amber-700 mb-4">Anda masih memakai password default. Segera ganti untuk keamanan akun.</p>
            @else
                <p class="text-sm text-muted mb-4">Perbarui password login panel Anda.</p>
            @endif

            <form method="POST" action="{{ route('user.profile.password') }}" class="space-y-4">
                @csrf

                @unless ($user->must_change_password)
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            required
                            class="app-input"
                            autocomplete="current-password"
                        >
                        @error('current_password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                @endunless

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        minlength="8"
                        class="app-input"
                        autocomplete="new-password"
                    >
                    @error('password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        minlength="8"
                        class="app-input"
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="app-btn w-full py-2.5 rounded-lg font-semibold">
                    Simpan Password
                </button>
            </form>
        </div>
    </div>
@endsection
