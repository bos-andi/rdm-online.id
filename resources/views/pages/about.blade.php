@extends('pages.layout')

@section('title', 'Tentang Kami — RDM-ONLINE.ID')
@section('eyebrow', 'Perusahaan')
@section('heading', 'Tentang Kami')

@section('page')
    @php($prices = app(\App\Services\PackagePricingService::class)->prices())

    <p>
        <strong>RDM Online</strong> (rdm-online.id) adalah layanan hosting aplikasi
        <strong>Rapor Digital Madrasah (RDM)</strong> berbasis langganan yang dikembangkan dan dikelola oleh
        <strong>{{ config('company.legal_name') }}</strong>. Kami membantu madrasah jenjang RA, MI, MTs, dan MA
        menjalankan aplikasi RDM secara online tanpa harus menyiapkan server, domain, atau keahlian teknis sendiri.
    </p>

    <h2>Apa yang Kami Sediakan</h2>
    <p>Setiap madrasah yang berlangganan mendapatkan satu aplikasi RDM siap pakai dengan:</p>
    <ul>
        <li>Subdomain sendiri, misalnya <em>namamadrasah.rdm-online.id</em>;</li>
        <li>Server dan database yang dikelola oleh tim kami;</li>
        <li>Sertifikat SSL (HTTPS) otomatis;</li>
        <li>Backup data berkala dan pemantauan server;</li>
        <li>Dukungan teknis melalui email dan WhatsApp.</li>
    </ul>

    <h2>Paket Layanan</h2>
    <ul>
        <li><strong>Paket Bulanan</strong> — Rp{{ number_format($prices['monthly'] ?? 0, 0, ',', '.') }} per bulan;</li>
        <li><strong>Paket Tahunan</strong> — Rp{{ number_format($prices['yearly'] ?? 0, 0, ',', '.') }} per tahun;</li>
        <li><strong>Paket 2 Tahunan</strong> — Rp{{ number_format($prices['biennial'] ?? 0, 0, ',', '.') }} per 2 tahun.</li>
    </ul>
    <p>Harga di atas belum termasuk biaya admin metode pembayaran yang ditampilkan saat checkout.</p>

    <h2>Cara Kerja</h2>
    <ol>
        <li>Madrasah mendaftar dan mengisi data di halaman <a href="{{ route('deploy') }}">Deploy RDM</a>.</li>
        <li>Madrasah memilih subdomain, paket, dan metode pembayaran (QRIS, Virtual Account, atau gerai retail).</li>
        <li>Setelah pembayaran terkonfirmasi, sistem otomatis membuat aplikasi RDM dan mengirimkan akses login.</li>
        <li>Langganan dapat diperpanjang kapan saja melalui dashboard atau halaman perpanjangan.</li>
    </ol>

    <h2>Visi Kami</h2>
    <p>
        Mempermudah digitalisasi administrasi penilaian di madrasah seluruh Indonesia dengan layanan yang terjangkau,
        aman, dan mudah digunakan.
    </p>

    <h2>Hubungi Kami</h2>
    @include('pages.partials.contact-details')
@endsection
