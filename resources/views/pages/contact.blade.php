@extends('pages.layout')

@section('title', 'Kontak — RDM-ONLINE.ID')
@section('eyebrow', 'Bantuan')
@section('heading', 'Hubungi Kami')

@section('page')
    <p>
        Ada pertanyaan seputar layanan, pembayaran, atau kendala teknis aplikasi RDM? Tim kami siap membantu melalui
        kontak berikut.
    </p>

    @include('pages.partials.contact-details')

    <h2>Sebelum Menghubungi</h2>
    <p>Agar kami dapat membantu lebih cepat, siapkan informasi berikut:</p>
    <ul>
        <li>Email akun dan subdomain RDM (misalnya <em>namamadrasah.rdm-online.id</em>);</li>
        <li>Nomor referensi transaksi jika pertanyaan terkait pembayaran;</li>
        <li>Tangkapan layar kendala yang dialami.</li>
    </ul>

    <p>
        <a href="https://wa.me/{{ config('company.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="landing-btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold no-underline">
            <i data-lucide="message-circle" class="w-5 h-5"></i>
            Chat WhatsApp
        </a>
    </p>
@endsection
