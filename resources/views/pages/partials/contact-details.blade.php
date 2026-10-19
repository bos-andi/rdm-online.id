<ul>
    <li><strong>Perusahaan:</strong> {{ config('company.legal_name') }}</li>
    <li><strong>Email:</strong> <a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a></li>
    <li><strong>WhatsApp:</strong> <a href="https://wa.me/{{ config('company.whatsapp') }}" target="_blank" rel="noopener noreferrer">+{{ config('company.whatsapp') }}</a></li>
    @if (filled(config('company.address')))
        <li><strong>Alamat:</strong> {{ config('company.address') }}</li>
    @endif
    <li><strong>Website:</strong> <a href="{{ route('landing') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a></li>
</ul>
