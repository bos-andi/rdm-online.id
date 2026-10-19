<x-mail::message>
# Peringatan Penghapusan RDM

Halo,

Langganan RDM untuk **{{ $instance->madrasah_name }}** (`{{ $instance->fqdn }}`) telah berakhir.

Data RDM Anda akan **dihapus permanen** pada **{{ $instance->purge_at?->translatedFormat('d F Y, H:i') }} WIB** jika tidak diperpanjang.

Sisa waktu: kurang dari {{ config('subscription.purge_warning_days', 7) }} hari.

Setelah dihapus permanen:
- Website RDM tidak dapat diakses
- Database dan file akan dihapus
- Subdomain dapat digunakan orang lain

<x-mail::button :url="route('renewal.show', $instance->subdomain)">
Perpanjang Sekarang
</x-mail::button>

Jika ada kendala, hubungi {{ config('company.email') }}.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
