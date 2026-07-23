<x-mail::message>
# Pengingat Pembayaran Deploy RDM

Halo,

Pembayaran untuk **{{ $instance->madrasah_name }}** (**{{ $instance->fqdn }}**) belum kami terima.

Tanpa pembayaran, RDM tidak dapat di-deploy dan subdomain akan dibebaskan dalam waktu **{{ config('deploy.pending_payment_ttl_hours', 24) }} jam** sejak pendaftaran.

**Cara melanjutkan:**

1. Login ke panel dengan email **{{ $instance->pic_email }}** dan password yang Anda buat saat pendaftaran.
2. Buka **RDM Saya** → klik **Lanjutkan Pembayaran**.
3. Pilih metode bayar (QRIS, Virtual Account, atau Indomaret) dan selesaikan pembayaran.

<x-mail::button :url="route('user.login')">
Masuk Panel
</x-mail::button>

<x-mail::button :url="route('deploy', ['resume' => $instance->id])">
Bayar Sekarang
</x-mail::button>

Butuh bantuan? Balas email ini atau hubungi support melalui panel pelanggan.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
