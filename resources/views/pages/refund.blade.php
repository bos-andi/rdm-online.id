@extends('pages.layout')

@section('title', 'Kebijakan Pengembalian Dana — RDM-ONLINE.ID')
@section('eyebrow', 'Legal')
@section('heading', 'Kebijakan Pengembalian Dana')
@section('updated', '4 Oktober 2026')

@section('page')
    <p>
        Kebijakan ini menjelaskan kondisi pengembalian dana (refund) untuk pembayaran layanan <strong>RDM Online</strong>
        yang dikelola oleh <strong>{{ config('company.legal_name') }}</strong>.
    </p>

    <h2>1. Pengembalian Dana yang Dapat Diajukan</h2>
    <p>Pelanggan dapat mengajukan pengembalian dana penuh apabila:</p>
    <ul>
        <li>Pembayaran berhasil, tetapi aplikasi RDM gagal dibuat dan tidak dapat kami perbaiki dalam 3 x 24 jam;</li>
        <li>Terjadi pembayaran ganda (double payment) untuk tagihan atau periode yang sama;</li>
        <li>Dana terpotong tetapi status pembayaran tidak terkonfirmasi dan layanan tidak aktif setelah kami lakukan pengecekan.</li>
    </ul>

    <h2>2. Pengembalian Dana yang Tidak Dapat Diajukan</h2>
    <ul>
        <li>Langganan yang sudah aktif dan dapat digunakan, termasuk jika Pelanggan berhenti menggunakan layanan sebelum masa aktif berakhir;</li>
        <li>Biaya admin metode pembayaran yang dikenakan oleh payment gateway, kecuali untuk kasus pembayaran ganda atau kegagalan dari pihak kami;</li>
        <li>Penghentian layanan akibat pelanggaran <a href="{{ route('pages.terms') }}">Syarat &amp; Ketentuan</a>;</li>
        <li>Kesalahan Pelanggan dalam memilih paket atau subdomain setelah layanan aktif. Untuk kasus ini, kami dapat membantu penyesuaian tanpa pengembalian dana.</li>
    </ul>

    <h2>3. Cara Mengajukan</h2>
    <ol>
        <li>Hubungi kami melalui email atau WhatsApp paling lambat 7 hari setelah tanggal pembayaran.</li>
        <li>Sertakan email akun, subdomain, nomor referensi transaksi, dan bukti pembayaran.</li>
        <li>Tim kami akan memverifikasi pengajuan dalam 1–3 hari kerja dan menginformasikan hasilnya.</li>
    </ol>

    <h2>4. Proses Pengembalian</h2>
    <p>
        Pengembalian dana yang disetujui ditransfer ke rekening bank atau e-wallet atas nama Pelanggan dalam waktu
        paling lambat 7 hari kerja setelah persetujuan. Pelanggan wajib memberikan data rekening yang benar.
    </p>

    <h2>5. Kontak</h2>
    @include('pages.partials.contact-details')
@endsection
