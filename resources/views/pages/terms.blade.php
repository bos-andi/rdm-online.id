@extends('pages.layout')

@section('title', 'Syarat & Ketentuan — RDM-ONLINE.ID')
@section('eyebrow', 'Legal')
@section('heading', 'Syarat & Ketentuan')
@section('updated', '4 Oktober 2026')

@section('page')
    <p>
        Syarat &amp; Ketentuan ini mengatur penggunaan layanan <strong>RDM Online</strong> di rdm-online.id
        ("Layanan") yang dikelola oleh <strong>{{ config('company.legal_name') }}</strong> ("kami").
        Dengan mendaftar, melakukan pembayaran, atau menggunakan Layanan, Anda ("Pelanggan") dianggap telah membaca,
        memahami, dan menyetujui seluruh ketentuan berikut.
    </p>

    <h2>1. Layanan</h2>
    <p>
        Layanan berupa penyediaan hosting aplikasi Rapor Digital Madrasah (RDM) berbasis langganan, meliputi subdomain
        di rdm-online.id, server, database, sertifikat SSL, backup berkala, dan dukungan teknis. Aplikasi RDM
        digunakan untuk keperluan administrasi penilaian madrasah.
    </p>

    <h2>2. Akun Pelanggan</h2>
    <ul>
        <li>Pelanggan wajib memberikan data yang benar dan lengkap saat mendaftar, termasuk nama madrasah dan email aktif.</li>
        <li>Pelanggan bertanggung jawab menjaga kerahasiaan kata sandi akun dan akun admin aplikasi RDM.</li>
        <li>Segala aktivitas yang terjadi melalui akun Pelanggan menjadi tanggung jawab Pelanggan.</li>
    </ul>

    <h2>3. Harga dan Pembayaran</h2>
    <ul>
        <li>Harga paket ditampilkan di halaman utama dan halaman checkout. Total pembayaran terdiri dari harga paket ditambah biaya admin sesuai metode pembayaran yang dipilih.</li>
        <li>Pembayaran diproses melalui mitra payment gateway resmi menggunakan QRIS, Virtual Account bank, atau gerai retail.</li>
        <li>Setiap tagihan memiliki batas waktu pembayaran. Tagihan yang melewati batas waktu otomatis dibatalkan dan Pelanggan dapat membuat tagihan baru.</li>
        <li>Layanan diaktifkan secara otomatis setelah pembayaran terkonfirmasi oleh sistem.</li>
        <li>Kami dapat mengubah harga paket sewaktu-waktu. Perubahan harga tidak berlaku untuk periode langganan yang sudah dibayar.</li>
    </ul>

    <h2>4. Masa Langganan dan Perpanjangan</h2>
    <ul>
        <li>Masa aktif dihitung sejak layanan aktif: 30 hari untuk Paket Bulanan, 365 hari untuk Paket Tahunan, dan 730 hari untuk Paket 2 Tahunan.</li>
        <li>Perpanjangan dilakukan secara manual oleh Pelanggan sebelum atau setelah masa aktif berakhir. Tidak ada penagihan otomatis.</li>
        <li>Jika langganan tidak diperpanjang, aplikasi RDM akan dinonaktifkan. Data disimpan selama {{ config('subscription.grace_days', 30) }} hari masa tenggang, dan pemberitahuan dikirim ke email Pelanggan sebelum data dihapus permanen.</li>
        <li>Setelah masa tenggang berakhir tanpa perpanjangan, data aplikasi RDM dihapus permanen dan tidak dapat dipulihkan.</li>
    </ul>

    <h2>5. Kewajiban dan Larangan</h2>
    <p>Pelanggan dilarang menggunakan Layanan untuk:</p>
    <ul>
        <li>Menyimpan atau menyebarkan konten yang melanggar hukum Republik Indonesia;</li>
        <li>Mengganggu, meretas, atau membebani server dan layanan pelanggan lain;</li>
        <li>Menjual kembali atau memindahtangankan Layanan tanpa persetujuan tertulis dari kami;</li>
        <li>Memasukkan data pribadi pihak lain tanpa dasar yang sah.</li>
    </ul>
    <p>Pelanggaran dapat mengakibatkan penangguhan atau penghentian Layanan tanpa pengembalian dana.</p>

    <h2>6. Data dan Backup</h2>
    <p>
        Data yang diinput ke aplikasi RDM adalah milik Pelanggan. Kami melakukan backup berkala sebagai langkah
        pencegahan, namun Pelanggan disarankan tetap menyimpan salinan data penting secara mandiri. Pengelolaan data
        pribadi dijelaskan dalam <a href="{{ route('pages.privacy') }}">Kebijakan Privasi</a>.
    </p>

    <h2>7. Ketersediaan Layanan</h2>
    <p>
        Kami berupaya menjaga Layanan tetap tersedia setiap saat. Layanan dapat terhenti sementara karena pemeliharaan
        terjadwal, gangguan pihak ketiga (penyedia server, jaringan, DNS), atau keadaan kahar. Kami akan
        menginformasikan pemeliharaan yang berdampak besar melalui pengumuman di dashboard.
    </p>

    <h2>8. Batasan Tanggung Jawab</h2>
    <p>
        Kami tidak bertanggung jawab atas kerugian tidak langsung yang timbul dari penggunaan Layanan, kesalahan input
        data oleh Pelanggan, atau kelalaian Pelanggan dalam menjaga keamanan akun. Total tanggung jawab kami terbatas
        pada nilai pembayaran Pelanggan untuk periode langganan yang sedang berjalan.
    </p>

    <h2>9. Pengembalian Dana</h2>
    <p>
        Ketentuan pengembalian dana diatur dalam <a href="{{ route('pages.refund') }}">Kebijakan Pengembalian Dana</a>.
    </p>

    <h2>10. Perubahan Ketentuan</h2>
    <p>
        Kami dapat memperbarui Syarat &amp; Ketentuan ini dari waktu ke waktu. Versi terbaru selalu tersedia di halaman
        ini. Penggunaan Layanan setelah perubahan berarti Pelanggan menyetujui ketentuan yang diperbarui.
    </p>

    <h2>11. Hukum yang Berlaku</h2>
    <p>
        Syarat &amp; Ketentuan ini tunduk pada hukum Republik Indonesia. Perselisihan diselesaikan terlebih dahulu
        secara musyawarah sebelum menempuh jalur hukum.
    </p>

    <h2>12. Kontak</h2>
    @include('pages.partials.contact-details')
@endsection
