@extends('layouts.landing')

@section('title', 'Program Affiliate — RDM-ONLINE.ID')

@php
    $service = app(\App\Services\AffiliateService::class);
    $rate = $service->formatRate($settings['default_rate']);
    $rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $yearlyCommission = (int) floor(($prices['yearly'] ?? 0) * $settings['default_rate'] / 100);
    $monthlyCommission = (int) floor(($prices['monthly'] ?? 0) * $settings['default_rate'] / 100);
@endphp

@section('content')
    <section class="relative pt-28 pb-16 lg:pt-36 lg:pb-24 overflow-hidden bg-gradient-to-b from-emerald-50 to-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 text-center">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-sm font-semibold">
                <i data-lucide="handshake" class="w-4 h-4"></i>
                Program Affiliate RDM Online
            </span>
            <h1 class="mt-6 text-4xl sm:text-5xl font-bold text-emerald-950 leading-tight">
                Kenalkan RDM Online,<br class="hidden sm:block"> dapatkan komisi <span class="text-emerald-600">{{ $rate }}</span> berulang
            </h1>
            <p class="mt-6 text-lg text-gray-600 max-w-2xl mx-auto">
                Bantu madrasah go digital dan dapatkan penghasilan tambahan. Komisi diberikan dari <strong>setiap pembayaran</strong> madrasah yang Anda ajak — termasuk setiap kali mereka memperpanjang langganan.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('affiliate.register') }}" class="landing-btn-primary inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl font-semibold shadow-lg shadow-emerald-200">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    Daftar Jadi Affiliate
                </a>
                <a href="{{ route('affiliate.login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl border-2 border-emerald-200 text-emerald-700 font-semibold hover:bg-emerald-50 transition-colors">
                    Sudah terdaftar? Masuk
                </a>
            </div>
            <p class="mt-4 text-sm text-gray-500">Gratis · Tanpa target · Cocok untuk operator, guru, pengawas, dan pegiat pendidikan</p>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-bold text-emerald-950">Cara Kerjanya</h2>
                <p class="mt-3 text-gray-600">Tiga langkah sederhana, semuanya tercatat otomatis.</p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'user-plus', 'title' => 'Daftar & dapatkan kode', 'text' => 'Isi formulir pendaftaran. Setelah disetujui, Anda mendapat kode referral dan link khusus.'],
                    ['icon' => 'share-2', 'title' => 'Bagikan ke madrasah', 'text' => 'Sebarkan link ke grup operator, KKM, atau kenalan. Madrasah juga bisa mengetik kode Anda saat mendaftar.'],
                    ['icon' => 'wallet', 'title' => 'Terima komisi', 'text' => 'Setiap pembayaran madrasah tersebut menambah saldo komisi Anda. Cairkan langsung ke rekening atau e-wallet.'],
                ] as $i => $step)
                    <div class="relative bg-white rounded-2xl border border-emerald-100 p-6 hover:shadow-lg transition-shadow">
                        <span class="absolute top-6 right-6 text-5xl font-bold text-emerald-50">{{ $i + 1 }}</span>
                        <span class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700">
                            <i data-lucide="{{ $step['icon'] }}" class="w-6 h-6"></i>
                        </span>
                        <h3 class="relative mt-4 text-lg font-semibold text-gray-900">{{ $step['title'] }}</h3>
                        <p class="relative mt-2 text-sm text-gray-600">{{ $step['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-20 bg-emerald-950 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 grid gap-10 lg:grid-cols-2 items-center">
            <div>
                <h2 class="text-3xl font-bold">Komisi yang terus bertambah</h2>
                <p class="mt-4 text-emerald-100/80">
                    Bukan hanya sekali. Selama madrasah yang Anda ajak terus berlangganan, Anda terus mendapatkan {{ $rate }} dari setiap pembayaran mereka.
                </p>
                <ul class="mt-6 space-y-3 text-sm text-emerald-50">
                    <li class="flex gap-3"><i data-lucide="check" class="w-5 h-5 text-emerald-400 shrink-0"></i>Komisi tercatat otomatis begitu pembayaran lunas</li>
                    <li class="flex gap-3"><i data-lucide="check" class="w-5 h-5 text-emerald-400 shrink-0"></i>Pantau klik, pendaftar, dan saldo di panel affiliate</li>
                    <li class="flex gap-3"><i data-lucide="check" class="w-5 h-5 text-emerald-400 shrink-0"></i>Pencairan ke rekening bank atau e-wallet, minimal {{ $rupiah($settings['min_payout']) }}</li>
                    <li class="flex gap-3"><i data-lucide="check" class="w-5 h-5 text-emerald-400 shrink-0"></i>Link referral berlaku {{ $settings['cookie_days'] }} hari sejak diklik</li>
                </ul>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-3xl p-8">
                <p class="text-sm text-emerald-200 uppercase tracking-wider font-semibold">Simulasi</p>
                <p class="mt-3 text-emerald-50">Ajak <strong class="text-white">10 madrasah</strong> berlangganan paket Tahunan ({{ $rupiah($prices['yearly'] ?? 0) }}):</p>
                <p class="mt-4 text-4xl font-bold text-emerald-300">{{ $rupiah($yearlyCommission * 10) }}<span class="text-base font-medium text-emerald-100/70"> / tahun</span></p>
                <p class="mt-3 text-sm text-emerald-100/70">
                    {{ $rupiah($yearlyCommission) }} per madrasah setiap tahun, atau {{ $rupiah($monthlyCommission) }} per bulan untuk paket Bulanan — dan terus berulang selama mereka memperpanjang.
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-emerald-950 text-center mb-10">Pertanyaan Umum</h2>
            <div class="space-y-4">
                @foreach ([
                    ['q' => 'Siapa yang boleh menjadi affiliate?', 'a' => 'Siapa saja — operator madrasah, guru, kepala madrasah, pengawas, hingga pegiat pendidikan. Pendaftaran gratis dan ditinjau oleh tim kami.'],
                    ['q' => 'Kapan komisi dihitung?', 'a' => 'Komisi tercatat otomatis setiap kali madrasah referral Anda menyelesaikan pembayaran, baik pendaftaran pertama maupun perpanjangan. Komisi dihitung dari harga paket, tidak termasuk biaya admin pembayaran.'],
                    ['q' => 'Bagaimana madrasah tercatat sebagai referral saya?', 'a' => 'Madrasah membuka link referral Anda lalu mendaftar dalam '.$settings['cookie_days'].' hari, atau mengisi kode referral Anda di formulir pendaftaran. Satu madrasah hanya tercatat untuk satu affiliate.'],
                    ['q' => 'Bagaimana cara mencairkan komisi?', 'a' => 'Sebelum pencairan pertama, verifikasi rekening Anda dengan mengupload foto KTP dan buku tabungan di panel affiliate. Setelah terverifikasi dan saldo mencapai '.$rupiah($settings['min_payout']).', ajukan pencairan dan tim kami mentransfer ke rekening yang terverifikasi tersebut.'],
                ] as $faq)
                    <details class="group bg-white rounded-2xl border border-emerald-100 p-5 open:shadow-md transition-shadow">
                        <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-gray-900">
                            {{ $faq['q'] }}
                            <i data-lucide="chevron-down" class="w-5 h-5 text-emerald-600 transition-transform group-open:rotate-180"></i>
                        </summary>
                        <p class="mt-3 text-sm text-gray-600 leading-relaxed">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('affiliate.register') }}" class="landing-btn-primary inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl font-semibold">
                    Daftar Sekarang — Gratis
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </section>
@endsection
