<x-mail::message>
@if ($accountCreated)
# Akun Panel RDM Online Anda

Halo {{ $user->name }},

Akun panel untuk **{{ $instance->madrasah_name }}** telah dibuat. RDM **{{ $instance->fqdn }}** menunggu pembayaran untuk diaktifkan.
@else
# RDM Menunggu Pembayaran

Halo {{ $user->name }},

Deploy **{{ $instance->fqdn }}** untuk **{{ $instance->madrasah_name }}** telah disiapkan dan menunggu pembayaran.
@endif

**Langkah selanjutnya:**

1. Masuk ke panel pelanggan dengan email **{{ $user->email }}** dan password yang Anda buat saat pendaftaran.
2. Buka menu **RDM Saya**.
3. Klik **Lanjutkan Pembayaran** pada RDM yang menunggu.
4. Pilih metode bayar dan selesaikan pembayaran — deploy otomatis setelah lunas.

<x-mail::button :url="route('user.login')">
Masuk Panel
</x-mail::button>

<x-mail::button :url="route('deploy', ['resume' => $instance->id])">
Lanjutkan Pembayaran
</x-mail::button>

Subdomain **{{ $instance->subdomain }}** direservasi selama **{{ config('deploy.pending_payment_ttl_hours', 24) }} jam**. Jika tidak dibayar, reservasi akan dibatalkan otomatis.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
