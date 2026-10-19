<x-mail::message>
# RDM Online Siap Digunakan

Halo,

Deploy RDM untuk **{{ $instance->madrasah_name }}** telah selesai.

**URL Akses:** [https://{{ $instance->fqdn }}](https://{{ $instance->fqdn }})

**Detail:**
- Subdomain: `{{ $instance->fqdn }}`
- Jenjang: {{ $instance->jenjang }}
- Paket: {{ config('deploy.packages.'.$instance->package.'.label') }}

Silakan buka URL di atas untuk melakukan setup awal aplikasi RDM.

<x-mail::button :url="'https://'.$instance->fqdn">
Buka RDM Online
</x-mail::button>

Jika ada kendala, hubungi {{ config('company.email') }}.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
