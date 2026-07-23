@extends('layouts.landing')

@section('content')
    {{-- Hero --}}
    <section class="relative pt-28 pb-20 lg:pt-36 lg:pb-28 overflow-hidden">
        <div class="landing-hero-bg absolute inset-0 -z-10"></div>
        <div class="landing-hero-glow absolute top-20 left-1/2 -translate-x-1/2 w-[600px] h-[600px] -z-10"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-6 landing-fade-up">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                        One Click Deploy — RDMhosting Siap Pakai
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-[3.25rem] font-bold leading-tight text-emerald-950 landing-fade-up landing-delay-1">
                        RDM Online Madrasah
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-green-500">dalam Satu Klik</span>
                    </h1>

                    <p class="mt-6 text-lg text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0 landing-fade-up landing-delay-2">
                        Deploy aplikasi RDM lengkap dari <strong class="text-emerald-700">RDMhosting</strong> secara otomatis.
                        DNS, database, SSL, dan subdomain siap dalam hitungan menit — tanpa ribet server.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start landing-fade-up landing-delay-3">
                        <a href="{{ route('deploy') }}" class="landing-btn-primary inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-base font-semibold shadow-lg shadow-emerald-200">
                            <i data-lucide="rocket" class="w-5 h-5"></i>
                            Mulai Deploy Gratis
                        </a>
                        <a href="#cara-kerja" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-base font-semibold border-2 border-emerald-200 text-emerald-700 hover:bg-emerald-50 transition-colors">
                            <i data-lucide="play-circle" class="w-5 h-5"></i>
                            Lihat Cara Kerja
                        </a>
                    </div>

                    <div class="mt-10 flex flex-wrap justify-center lg:justify-start gap-6 text-sm text-gray-500 landing-fade-up landing-delay-4">
                        <span class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i> Tanpa coding</span>
                        <span class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i> SSL otomatis</span>
                        <span class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i> Backup harian</span>
                    </div>
                </div>

                {{-- Hero visual: deploy preview --}}
                <div class="relative landing-fade-up landing-delay-2">
                    <div class="landing-mockup rounded-2xl border border-emerald-100 bg-white shadow-2xl shadow-emerald-100/80 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border-b border-emerald-100">
                            <div class="flex gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-rose-300"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-300"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                            </div>
                            <span class="flex-1 text-center text-xs text-gray-400 font-mono">rdm-online.id/deploy</span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                                    <i data-lucide="school" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">MTS / MA Al-Ikhlas</p>
                                    <p class="text-xs text-gray-400">alikhlas.rdm-online.id</p>
                                </div>
                                <span class="ml-auto px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Deploying...</span>
                            </div>

                            <div class="space-y-2.5 text-sm">
                                @foreach (['DNS Cloudflare', 'Database MySQL', 'Copy RDMhosting', 'Konfigurasi Nginx', 'Install SSL'] as $i => $step)
                                    <div class="flex items-center gap-3 {{ $i < 3 ? 'text-emerald-600' : 'text-gray-400' }}">
                                        <i data-lucide="{{ $i < 3 ? 'check-circle' : ($i === 3 ? 'loader' : 'circle') }}" class="w-4 h-4 {{ $i === 3 ? 'animate-spin' : '' }}"></i>
                                        <span>{{ $step }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 p-3 rounded-xl bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-100">
                                <p class="text-xs text-emerald-700 font-medium">Estimasi selesai: ~2 menit</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-4 -left-4 landing-float-card bg-white rounded-xl shadow-lg border border-emerald-100 px-4 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">SSL Status</p>
                            <p class="text-sm font-semibold text-emerald-700">Active</p>
                        </div>
                    </div>

                    <div class="absolute -top-3 -right-3 landing-float-card-delay bg-white rounded-xl shadow-lg border border-emerald-100 px-4 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <i data-lucide="server" class="w-4 h-4 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Uptime</p>
                            <p class="text-sm font-semibold text-green-700">99.9%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="py-12 bg-emerald-600">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center text-white">
                @foreach ([
                    ['value' => '312+', 'label' => 'RDM Aktif'],
                    ['value' => '147+', 'label' => 'Madrasah Terdaftar'],
                    ['value' => '99.9%', 'label' => 'Uptime Server'],
                    ['value' => '< 3 menit', 'label' => 'Waktu Deploy'],
                ] as $stat)
                    <div>
                        <p class="text-3xl sm:text-4xl font-bold">{{ $stat['value'] }}</p>
                        <p class="mt-1 text-emerald-100 text-sm">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Fitur --}}
    <section id="fitur" class="py-20 lg:py-28 bg-gradient-to-b from-white to-emerald-50/50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-emerald-600 font-semibold text-sm uppercase tracking-wider">Fitur Lengkap</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-emerald-950">Semua yang Madrasah Butuhkan</h2>
                <p class="mt-4 text-gray-600">Dari deploy hingga maintenance — semuanya otomatis dan terkelola dalam satu panel.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['icon' => 'mouse-pointer-click', 'title' => 'One Click Deploy', 'desc' => 'Isi nama madrasah & subdomain, klik deploy. RDMhosting otomatis terpasang lengkap.'],
                    ['icon' => 'globe', 'title' => 'DNS & SSL Otomatis', 'desc' => 'Subdomain .rdm-online.id dengan sertifikat SSL gratis via Cloudflare.'],
                    ['icon' => 'database', 'title' => 'Database Siap Pakai', 'desc' => 'MySQL database dibuat otomatis dan dikonfigurasi untuk aplikasi RDM.'],
                    ['icon' => 'hard-drive', 'title' => 'Backup Harian', 'desc' => 'Backup otomatis setiap hari pukul 02:00 WIB. Data madrasah aman terjaga.'],
                    ['icon' => 'activity', 'title' => 'Monitoring Real-time', 'desc' => 'Pantau CPU, RAM, storage, dan status server langsung dari dashboard.'],
                    ['icon' => 'message-circle', 'title' => 'Support WhatsApp', 'desc' => 'Tim support siap membantu via WhatsApp untuk kendala teknis.'],
                ] as $feature)
                    <div class="landing-feature-card group p-6 rounded-2xl bg-white border border-emerald-100 hover:border-emerald-200 transition-all duration-300">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 group-hover:bg-emerald-600 flex items-center justify-center transition-colors duration-300">
                            <i data-lucide="{{ $feature['icon'] }}" class="w-6 h-6 text-emerald-600 group-hover:text-white transition-colors duration-300"></i>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-gray-500 text-sm leading-relaxed">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Cara Kerja --}}
    <section id="cara-kerja" class="py-20 lg:py-28">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-emerald-600 font-semibold text-sm uppercase tracking-wider">Mudah & Cepat</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-emerald-950">3 Langkah RDM Online</h2>
                <p class="mt-4 text-gray-600">Tidak perlu keahlian teknis. Cukup 3 langkah dan madrasah Anda sudah punya RDM online.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                <div class="hidden md:block absolute top-16 left-[20%] right-[20%] h-0.5 bg-gradient-to-r from-emerald-200 via-emerald-400 to-emerald-200"></div>

                @foreach ([
                    ['step' => '01', 'icon' => 'file-text', 'title' => 'Isi Data Madrasah', 'desc' => 'Masukkan nama madrasah, pilih jenjang (RA/MI/MTs/MA), dan tentukan subdomain.'],
                    ['step' => '02', 'icon' => 'rocket', 'title' => 'Klik Deploy', 'desc' => 'Sistem otomatis membuat DNS, database, copy RDMhosting, konfigurasi server, dan install SSL.'],
                    ['step' => '03', 'icon' => 'check-circle', 'title' => 'RDM Siap Digunakan', 'desc' => 'Akses RDM madrasah Anda via subdomain. Akun admin awal dikirim via WhatsApp.'],
                ] as $item)
                    <div class="relative text-center">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg shadow-emerald-200 relative z-10">
                            <i data-lucide="{{ $item['icon'] }}" class="w-7 h-7 text-white"></i>
                        </div>
                        <span class="inline-block mt-4 text-xs font-bold text-emerald-500 tracking-widest">LANGKAH {{ $item['step'] }}</span>
                        <h3 class="mt-2 text-xl font-semibold text-gray-900">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-gray-500 text-sm leading-relaxed px-4">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- RDMhosting highlight --}}
    <section class="py-20 bg-emerald-950 text-white overflow-hidden relative">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-400 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-green-400 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 relative">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-emerald-400 font-semibold text-sm uppercase tracking-wider">Engine RDM</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-bold">Berdasarkan RDMhosting Teruji</h2>
                    <p class="mt-4 text-emerald-200/80 leading-relaxed">
                        Setiap deploy menggunakan paket <strong class="text-white">RDMhosting</strong> — aplikasi Rapor Digital Madrasah
                        yang sudah digunakan ratusan madrasah di Indonesia. Source code lengkap, database terstruktur,
                        dan siap produksi.
                    </p>
                    <ul class="mt-8 space-y-4">
                        @foreach (['Rapor Digital lengkap per jenjang', 'Manajemen siswa, guru & kelas', 'Cetak rapor & export data', 'Multi-user dengan hak akses'] as $point)
                            <li class="flex items-center gap-3 text-emerald-100">
                                <i data-lucide="check" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @foreach ([
                        ['icon' => 'users', 'label' => 'Multi User'],
                        ['icon' => 'file-spreadsheet', 'label' => 'Rapor Digital'],
                        ['icon' => 'printer', 'label' => 'Cetak Rapor'],
                        ['icon' => 'shield', 'label' => 'Data Aman'],
                    ] as $card)
                        <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur text-center hover:bg-white/10 transition-colors">
                            <i data-lucide="{{ $card['icon'] }}" class="w-8 h-8 text-emerald-400 mx-auto"></i>
                            <p class="mt-3 font-medium text-sm">{{ $card['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Harga --}}
    <section id="harga" class="py-20 lg:py-28 bg-gradient-to-b from-emerald-50/50 to-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-emerald-600 font-semibold text-sm uppercase tracking-wider">Harga Transparan</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-emerald-950">Pilih Paket Sesuai Kebutuhan</h2>
                <p class="mt-4 text-gray-600">1 akun = 1 RDM madrasah. Semua paket termasuk hosting, SSL, backup, dan subdomain gratis.</p>

                @if ($pricing['promo_active'] && $pricing['promo_ends_at'])
                    <div
                        class="mt-6 inline-flex flex-col sm:flex-row items-center gap-2 sm:gap-4 px-5 py-3 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-200"
                        data-promo-countdown
                        data-ends-at="{{ $pricing['promo_ends_at'] }}"
                    >
                        <span class="text-sm font-medium opacity-90">Promo berakhir dalam</span>
                        <div class="flex items-center gap-2 font-mono text-lg font-bold tracking-wide">
                            <span data-cd="d">00</span><span class="opacity-70 text-sm font-sans">hari</span>
                            <span data-cd="h">00</span><span class="opacity-70 text-sm font-sans">jam</span>
                            <span data-cd="m">00</span><span class="opacity-70 text-sm font-sans">mnt</span>
                            <span data-cd="s">00</span><span class="opacity-70 text-sm font-sans">dtk</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid md:grid-cols-2 gap-6 lg:gap-8 max-w-3xl mx-auto">
                @foreach ([
                    [
                        'name' => 'Bulanan',
                        'desc' => 'Bayar per bulan, fleksibel',
                        'price' => $pricing['monthly'],
                        'period' => '/bulan',
                        'original' => $pricing['monthly_original'],
                        'badge' => $pricing['monthly_original'] ? 'Promo' : null,
                        'popular' => false,
                        'features' => ['Backup Harian', 'Subdomain Gratis', 'SSL Gratis', 'Monitoring Real-time', 'Support WhatsApp'],
                    ],
                    [
                        'name' => 'Tahunan',
                        'desc' => 'Hemat lebih banyak',
                        'price' => $pricing['yearly'],
                        'period' => '/tahun',
                        'original' => $pricing['yearly_original'],
                        'badge' => $pricing['yearly_original'] ? 'Promo' : null,
                        'popular' => true,
                        'features' => ['Backup Harian', 'Subdomain Gratis', 'SSL Gratis', 'Monitoring Real-time', 'Priority Support'],
                    ],
                ] as $plan)
                    <div @class([
                        'relative rounded-2xl p-8 transition-all duration-300',
                        'bg-white border-2 border-emerald-500 shadow-xl shadow-emerald-100 scale-[1.02]' => $plan['popular'],
                        'bg-white border border-emerald-100 hover:border-emerald-200 hover:shadow-lg' => ! $plan['popular'],
                    ])>
                        @if ($plan['badge'])
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-emerald-600 text-white text-xs font-semibold">
                                {{ $plan['badge'] }}
                            </span>
                        @endif

                        <h3 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $plan['desc'] }}</p>

                        <div class="mt-6">
                            @if ($plan['original'])
                                <p class="text-gray-400 text-lg line-through">Rp{{ number_format($plan['original'], 0, ',', '.') }}</p>
                            @endif
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-emerald-700">Rp{{ number_format($plan['price'], 0, ',', '.') }}</span>
                                <span class="text-gray-400 text-sm">{{ $plan['period'] }}</span>
                            </div>
                        </div>

                        <ul class="mt-8 space-y-3">
                            @foreach ($plan['features'] as $feat)
                                <li class="flex items-center gap-3 text-sm text-gray-600">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                                    {{ $feat }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('deploy') }}" @class([
                            'mt-8 block text-center py-3 rounded-xl font-semibold transition-colors',
                            'landing-btn-primary' => $plan['popular'],
                            'border-2 border-emerald-200 text-emerald-700 hover:bg-emerald-50' => ! $plan['popular'],
                        ])>
                            Pilih {{ $plan['name'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="py-20 lg:py-28">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-14">
                <span class="text-emerald-600 font-semibold text-sm uppercase tracking-wider">FAQ</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-emerald-950">Pertanyaan Umum</h2>
            </div>

            <div class="space-y-4">
                @foreach ([
                    ['q' => 'Apa itu RDM Online?', 'a' => 'RDM Online adalah layanan hosting untuk aplikasi Rapor Digital Madrasah (RDM) berbasis RDMhosting. Madrasah bisa memiliki sistem rapor digital sendiri dengan subdomain unik.'],
                    ['q' => 'Berapa lama proses deploy?', 'a' => 'Proses deploy otomatis biasanya selesai dalam 2-3 menit. Sistem akan membuat DNS, database, copy source RDMhosting, konfigurasi server, dan install SSL secara otomatis.'],
                    ['q' => 'Apakah perlu keahlian teknis?', 'a' => 'Tidak sama sekali. Cukup isi form deploy dengan data madrasah, klik tombol deploy, dan tunggu hingga selesai. Akun admin akan dikirim via WhatsApp.'],
                    ['q' => 'Apakah data aman?', 'a' => 'Ya. Setiap RDM memiliki database terpisah, SSL aktif, dan backup otomatis setiap hari. Server dipantau 24/7 dengan uptime 99.9%.'],
                ] as $faq)
                    <details class="group landing-faq rounded-2xl border border-emerald-100 bg-white overflow-hidden">
                        <summary class="flex items-center justify-between p-5 cursor-pointer font-semibold text-gray-900 hover:bg-emerald-50/50 transition-colors">
                            {{ $faq['q'] }}
                            <i data-lucide="chevron-down" class="w-5 h-5 text-emerald-500 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-5 pb-5 text-gray-600 text-sm leading-relaxed">
                            {{ $faq['a'] }}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="relative rounded-3xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-600"></div>
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl"></div>
                </div>

                <div class="relative px-8 py-14 sm:px-14 sm:py-16 text-center text-white">
                    <h2 class="text-3xl sm:text-4xl font-bold">Siap Go Digital?</h2>
                    <p class="mt-4 text-emerald-100 text-lg max-w-xl mx-auto">
                        Deploy RDM madrasah Anda sekarang. Satu klik, langsung online dengan RDMhosting siap pakai.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('deploy') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-white text-emerald-700 font-semibold hover:bg-emerald-50 transition-colors shadow-lg">
                            <i data-lucide="rocket" class="w-5 h-5"></i>
                            Deploy RDM Sekarang
                        </a>
                        <a href="https://wa.me/628123456789" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl border-2 border-white/30 text-white font-semibold hover:bg-white/10 transition-colors">
                            <i data-lucide="message-circle" class="w-5 h-5"></i>
                            Hubungi Kami
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
