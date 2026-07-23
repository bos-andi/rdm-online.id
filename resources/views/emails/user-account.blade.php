<x-mail::message>
@if ($isReset)
# Password Akun Direset

Halo {{ $user->name }},

Password panel RDM Online Anda telah direset oleh administrator.
@else
# Akun Panel RDM Online

Halo {{ $user->name }},

Akun panel RDM Online untuk **{{ $user->madrasah_name ?? $user->name }}** telah dibuat.
@endif

**Login panel:** [{{ route('user.login') }}]({{ route('user.login') }})

**Email:** {{ $user->email }}

**Password sementara:** `{{ $plainPassword }}`

<x-mail::button :url="route('user.login')">
Masuk Panel
</x-mail::button>

Demi keamanan, segera ganti password setelah login pertama kali.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
