@extends('pages.layout')

@section('title', 'Kebijakan Privasi — RDM-ONLINE.ID')
@section('eyebrow', 'Legal')
@section('heading', 'Kebijakan Privasi')
@section('updated', '4 Oktober 2026')

@section('page')
    <p>
        <strong>{{ config('company.legal_name') }}</strong> ("kami") menghargai privasi Anda. Kebijakan Privasi ini
        menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi data pribadi saat Anda
        menggunakan layanan <strong>RDM Online</strong> di rdm-online.id, sesuai dengan Undang-Undang Nomor 27 Tahun
        2022 tentang Pelindungan Data Pribadi.
    </p>

    <h2>1. Data yang Kami Kumpulkan</h2>
    <p><strong>a. Data akun dan pelanggan</strong></p>
    <ul>
        <li>Nama penanggung jawab (PIC), alamat email, dan kata sandi (disimpan dalam bentuk terenkripsi/hash);</li>
        <li>Nama madrasah, jenjang, dan subdomain yang dipilih;</li>
        <li>Nomor WhatsApp jika Anda memberikannya untuk keperluan dukungan.</li>
    </ul>
    <p><strong>b. Data transaksi</strong></p>
    <ul>
        <li>Paket yang dipilih, nominal, metode pembayaran, status, dan nomor referensi transaksi;</li>
        <li>Kami <strong>tidak</strong> menyimpan data kartu, PIN, atau kredensial perbankan Anda. Pembayaran diproses langsung oleh mitra payment gateway.</li>
    </ul>
    <p><strong>c. Data teknis</strong></p>
    <ul>
        <li>Alamat IP, jenis peramban, waktu akses, dan log aktivitas untuk keamanan serta pemecahan masalah;</li>
        <li>Cookie sesi yang diperlukan agar Anda tetap masuk ke akun.</li>
    </ul>
    <p><strong>d. Data di dalam aplikasi RDM</strong></p>
    <p>
        Data siswa, guru, dan nilai yang Anda input ke aplikasi RDM adalah milik madrasah. Kami bertindak sebagai
        pemroses data yang menyimpan data tersebut di server atas nama madrasah, dan tidak menggunakannya untuk
        keperluan lain.
    </p>

    <h2>2. Penggunaan Data</h2>
    <p>Data digunakan untuk:</p>
    <ul>
        <li>Membuat akun, menyiapkan, dan menjalankan aplikasi RDM Anda;</li>
        <li>Memproses pembayaran dan mengaktifkan atau memperpanjang langganan;</li>
        <li>Mengirim informasi penting seperti akses login, tagihan, pengingat masa aktif, dan pemberitahuan penghapusan data;</li>
        <li>Memberikan dukungan teknis dan menanggapi pertanyaan;</li>
        <li>Menjaga keamanan layanan serta mencegah penyalahgunaan.</li>
    </ul>

    <h2>3. Berbagi Data dengan Pihak Ketiga</h2>
    <p>Kami tidak menjual data pribadi Anda. Data hanya dibagikan seperlunya kepada:</p>
    <ul>
        <li><strong>Mitra payment gateway</strong> (nama, email, dan nominal transaksi) untuk memproses pembayaran;</li>
        <li><strong>Penyedia infrastruktur</strong> (server, DNS, dan email) yang membantu menjalankan layanan;</li>
        <li><strong>Pihak berwenang</strong> apabila diwajibkan oleh peraturan perundang-undangan.</li>
    </ul>

    <h2>4. Penyimpanan dan Keamanan</h2>
    <ul>
        <li>Koneksi ke layanan dilindungi enkripsi HTTPS/SSL;</li>
        <li>Kata sandi disimpan dalam bentuk hash dan tidak dapat dibaca oleh tim kami;</li>
        <li>Akses ke server dibatasi hanya untuk personel yang berwenang;</li>
        <li>Backup dilakukan secara berkala untuk mencegah kehilangan data.</li>
    </ul>

    <h2>5. Masa Penyimpanan</h2>
    <p>
        Data akun dan aplikasi RDM disimpan selama langganan aktif. Jika langganan berakhir dan tidak diperpanjang,
        data aplikasi RDM dihapus permanen setelah masa tenggang {{ config('subscription.grace_days', 30) }} hari.
        Data transaksi dapat disimpan lebih lama untuk keperluan pembukuan dan kewajiban hukum.
    </p>

    <h2>6. Hak Anda</h2>
    <p>Anda berhak untuk:</p>
    <ul>
        <li>Mengakses dan memperbarui data akun Anda;</li>
        <li>Meminta salinan data aplikasi RDM madrasah Anda;</li>
        <li>Meminta penghapusan akun dan data, dengan memperhatikan kewajiban penyimpanan data transaksi;</li>
        <li>Menarik persetujuan atas pengiriman informasi yang tidak wajib.</li>
    </ul>
    <p>Permintaan dapat diajukan melalui kontak di bawah dan akan kami tanggapi paling lambat 3 x 24 jam hari kerja.</p>

    <h2>7. Cookie</h2>
    <p>
        Kami hanya menggunakan cookie yang diperlukan untuk sesi login dan keamanan formulir (CSRF). Kami tidak
        menggunakan cookie iklan pihak ketiga.
    </p>

    <h2>8. Perubahan Kebijakan</h2>
    <p>
        Kebijakan Privasi ini dapat diperbarui sewaktu-waktu. Perubahan penting akan diinformasikan melalui email
        atau pengumuman di dashboard.
    </p>

    <h2>9. Kontak</h2>
    @include('pages.partials.contact-details')
@endsection
