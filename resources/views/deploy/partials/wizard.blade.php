<div
    id="deploy-wizard"
    class="scroll-mt-24 w-full max-w-xl mx-auto text-left"
    data-guest="{{ $isGuest ? '1' : '0' }}"
    data-admin-fees='@json($adminFees ?? [])'
    data-package-prices='@json($packagePrices ?? ['monthly' => 15000, 'yearly' => 50000, 'biennial' => 200000])'
    data-va-methods='@json(collect($paymentMethods ?? [])->flatten(1)->where("group", "Virtual Account")->pluck("code")->values())'
>
    <div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/60 overflow-hidden border border-emerald-100">
        <div id="wizard-form-view">
            <div class="px-6 pt-6 pb-4 border-b border-emerald-100">
                <div class="mb-4">
                    <h2 class="font-bold text-emerald-900">Deploy RDM Baru</h2>
                    @if ($isGuest)
                        <p class="text-xs text-gray-500 mt-0.5">Akun dibuat otomatis saat lanjut ke pembayaran</p>
                    @endif
                </div>
                <ol class="grid grid-cols-4 gap-2">
                    @foreach ([1 => 'Subdomain', 2 => 'Data', 3 => 'Paket', 4 => 'Bayar'] as $s => $stepLabel)
                        <li>
                            <div class="wizard-step-dot h-1.5 rounded-full bg-emerald-100 transition-colors" data-step="{{ $s }}"></div>
                            <p class="mt-1.5 text-xs text-gray-400 transition-colors" data-step-label="{{ $s }}">{{ $s }}. {{ $stepLabel }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="p-6">
                {{-- Step 1: Subdomain --}}
                <div class="wizard-panel" data-panel="1">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="w-subdomain">Subdomain</label>
                    <div class="flex">
                        <input id="w-subdomain" type="text" class="app-input rounded-r-none" placeholder="namamadrasah" autocomplete="off">
                        <span class="bg-emerald-50 border border-emerald-200 border-l-0 rounded-r-lg px-3 py-2.5 text-sm text-emerald-700 whitespace-nowrap">.rdm-online.id</span>
                    </div>
                    <p id="subdomain-status" class="text-sm mt-2 hidden"></p>
                </div>

                {{-- Step 2: Data + Akun --}}
                <div class="wizard-panel hidden" data-panel="2">
                    <div class="space-y-4">
                        @if ($isGuest)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="w-pic-name">Nama PIC / Penanggung Jawab</label>
                                <input id="w-pic-name" type="text" class="app-input" placeholder="Ustadz Ahmad">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="w-email">Email (jadi akun login)</label>
                                <input id="w-email" type="email" class="app-input" placeholder="admin@madrasah.sch.id" autocomplete="email">
                                <p id="email-status" class="text-sm mt-2 hidden"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <x-password-input id="w-password" label="Password" placeholder="Min. 8 karakter" />
                                <x-password-input id="w-password-confirm" label="Konfirmasi" placeholder="Ulangi password" />
                            </div>
                            <p id="password-status" class="text-sm text-gray-400 -mt-1"></p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="w-referral">Kode Referral <span class="font-normal text-gray-400">(opsional)</span></label>
                                <input id="w-referral" type="text" class="app-input uppercase placeholder:normal-case" maxlength="30" value="{{ strtoupper(is_string(request()->query('ref')) ? request()->query('ref') : (string) request()->cookie(\App\Services\AffiliateService::COOKIE)) }}" placeholder="Isi jika direkomendasikan seseorang" autocomplete="off">
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="w-email">Email</label>
                                <input id="w-email" type="email" class="app-input bg-emerald-50" value="{{ $user->email }}" readonly>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="w-nama">Nama Madrasah</label>
                            <input id="w-nama" type="text" class="app-input" value="{{ $user->madrasah_name ?? $user->name ?? '' }}" placeholder="MI Nurul Huda">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="w-jenjang">Jenjang</label>
                            <select id="w-jenjang" class="app-input">
                                @foreach (['RA', 'MI', 'MTs', 'MA'] as $j)
                                    <option value="{{ $j }}" @selected(($user->jenjang ?? 'MI') === $j)>{{ $j }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="wizard-step-hint" class="hidden mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm"></div>
                </div>

                {{-- Step 3: Paket --}}
                <div class="wizard-panel hidden" data-panel="3">
                    @php
                        $fmt = fn (int $n) => number_format($n, 0, ',', '.');
                        $mPrice = (int) ($packagePrices['monthly'] ?? 15000);
                        $yPrice = (int) ($packagePrices['yearly'] ?? 50000);
                        $bPrice = (int) ($packagePrices['biennial'] ?? 200000);
                        $mOriginal = $pricingDisplay['monthly_original'] ?? null;
                        $yOriginal = $pricingDisplay['yearly_original'] ?? null;
                        $bOriginal = $pricingDisplay['biennial_original'] ?? null;
                    @endphp
                    <div class="space-y-3">
                        <label class="block cursor-pointer">
                            <input type="radio" name="package" value="monthly" class="peer sr-only" checked>
                            <div class="p-4 rounded-xl border-2 border-emerald-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            Bulanan
                                            @if ($mOriginal)
                                                <span class="text-xs bg-emerald-600 text-white px-2 py-0.5 rounded-full ml-1">Promo</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-muted">
                                            @if ($mOriginal)
                                                <s class="text-gray-400">Rp{{ $fmt($mOriginal) }}</s>
                                            @endif
                                            Bayar per bulan
                                        </p>
                                    </div>
                                    <p class="font-bold text-emerald-700">Rp{{ $fmt($mPrice) }}<span class="text-sm font-normal text-muted">/bln</span></p>
                                </div>
                            </div>
                        </label>
                        <label class="block cursor-pointer">
                            <input type="radio" name="package" value="yearly" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-emerald-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            Tahunan
                                            @if ($yOriginal)
                                                <span class="text-xs bg-emerald-600 text-white px-2 py-0.5 rounded-full ml-1">Promo</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-muted">
                                            @if ($yOriginal)
                                                <s class="text-gray-400">Rp{{ $fmt($yOriginal) }}</s>
                                                Rp{{ $fmt($yPrice) }}/tahun
                                            @else
                                                Bayar per tahun
                                            @endif
                                        </p>
                                    </div>
                                    <p class="font-bold text-emerald-700">Rp{{ $fmt($yPrice) }}<span class="text-sm font-normal text-muted">/thn</span></p>
                                </div>
                            </div>
                        </label>
                        <label class="block cursor-pointer">
                            <input type="radio" name="package" value="biennial" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-emerald-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            2 Tahunan
                                            @if ($bOriginal)
                                                <span class="text-xs bg-emerald-600 text-white px-2 py-0.5 rounded-full ml-1">Promo</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-muted">
                                            @if ($bOriginal)
                                                <s class="text-gray-400">Rp{{ $fmt($bOriginal) }}</s>
                                                Rp{{ $fmt($bPrice) }}/2 tahun
                                            @else
                                                Bayar per 2 tahun
                                            @endif
                                        </p>
                                    </div>
                                    <p class="font-bold text-emerald-700">Rp{{ $fmt($bPrice) }}<span class="text-sm font-normal text-muted">/2 thn</span></p>
                                </div>
                            </div>
                        </label>
                    </div>
                    @if (! empty($pricingDisplay['promo_active']) && ! empty($pricingDisplay['promo_ends_at']))
                        <div
                            class="mt-4 p-3 rounded-xl bg-emerald-600 text-white text-center"
                            data-promo-countdown
                            data-ends-at="{{ $pricingDisplay['promo_ends_at'] }}"
                        >
                            <p class="text-xs font-medium opacity-90 mb-1">Promo berakhir dalam</p>
                            <div class="flex items-center justify-center gap-2 font-mono text-sm font-bold">
                                <span data-cd="d">00</span><span class="opacity-70 text-xs font-sans">h</span>
                                <span data-cd="h">00</span><span class="opacity-70 text-xs font-sans">j</span>
                                <span data-cd="m">00</span><span class="opacity-70 text-xs font-sans">m</span>
                                <span data-cd="s">00</span><span class="opacity-70 text-xs font-sans">d</span>
                            </div>
                        </div>
                    @endif
                    <div id="deploy-prepare-error" class="hidden mt-3 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm"></div>
                </div>
                <div class="wizard-panel hidden" data-panel="4">
                    <div class="space-y-3 text-sm bg-emerald-50 rounded-xl p-4 border border-emerald-100 mb-4">
                        <div class="flex justify-between"><span class="text-muted">Subdomain</span><span id="c-fqdn" class="font-medium text-emerald-700"></span></div>
                        @if ($isGuest)
                            <div class="flex justify-between"><span class="text-muted">PIC</span><span id="c-pic" class="font-medium"></span></div>
                        @endif
                        <div class="flex justify-between"><span class="text-muted">Madrasah</span><span id="c-nama" class="font-medium"></span></div>
                        <div class="flex justify-between"><span class="text-muted">Jenjang</span><span id="c-jenjang" class="font-medium"></span></div>
                        <div class="flex justify-between"><span class="text-muted">Email</span><span id="c-email" class="font-medium"></span></div>
                        <div class="flex justify-between"><span class="text-muted">Paket</span><span id="c-paket" class="font-medium"></span></div>
                        <div class="flex justify-between pt-2 border-t border-emerald-200"><span class="text-muted">Harga paket</span><span id="c-harga" class="font-medium"></span></div>
                        <div class="flex justify-between"><span class="text-muted">Biaya admin</span><span id="c-admin" class="font-medium text-gray-400">—</span></div>
                        <div class="flex justify-between font-bold text-emerald-700 pt-1"><span>Total bayar</span><span id="c-total"></span></div>
                    </div>

                    @if ($isGuest)
                        <p class="text-xs text-muted mb-3" id="deploy-account-hint">Akun login dibuat saat Anda klik <strong>Buat Akun & Lanjut Bayar</strong> di bawah.</p>
                    @endif

                    <div id="deploy-prepare-notice" class="hidden mb-3 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"></div>

                    <h4 class="text-sm font-semibold text-gray-800 mb-2">Pilih metode pembayaran</h4>
                    <p class="text-xs text-gray-500 mb-3" id="deploy-payment-hint">QRIS & Virtual Account ditampilkan langsung di halaman ini · metode lain diarahkan ke halaman pembayaran {{ config('payment.label') }}</p>

                    <div id="deploy-payment-error" class="hidden mb-3 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm"></div>

                    <div class="space-y-4" id="deploy-payment-methods">
                        @forelse ($paymentMethods ?? [] as $group => $methods)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-2">{{ $group }}</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($methods as $method)
                                        @php($fee = $adminFees[$method['code']] ?? 0)
                                        <label class="flex items-start gap-2 p-3 rounded-lg border-2 border-gray-200 cursor-pointer hover:border-emerald-300 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50">
                                            <input
                                                type="radio"
                                                name="deploy_payment_method"
                                                value="{{ $method['code'] }}"
                                                data-admin-fee="{{ $fee }}"
                                                class="mt-0.5 text-emerald-600"
                                            >
                                            <span class="min-w-0 text-sm">
                                                <span class="font-semibold text-gray-900">{{ $method['label'] }}</span>
                                                @if ($fee > 0)
                                                    <span class="block text-xs text-amber-700">+ Rp{{ number_format($fee, 0, ',', '.') }} admin</span>
                                                @else
                                                    <span class="block text-xs text-emerald-700">Tanpa biaya admin</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-rose-600">Metode pembayaran belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="px-6 pb-6 flex gap-3 border-t border-emerald-50 pt-4">
                <button type="button" id="wizard-prev" class="flex-1 py-2.5 rounded-xl border border-emerald-200 text-emerald-700 font-medium hidden">Kembali</button>
                <button type="button" id="wizard-next" class="flex-1 app-btn py-2.5 rounded-xl font-semibold" disabled>Lanjut</button>
            </div>
        </div>

        <div id="wizard-qris-view" class="hidden">
            <div class="px-6 pt-6 pb-4 border-b border-emerald-100">
                <h2 class="font-bold text-emerald-900" id="inline-payment-title">Bayar</h2>
                <p class="text-xs text-gray-500 mt-0.5" id="inline-payment-subtitle">Selesaikan pembayaran di bawah</p>
            </div>

            <div class="p-6 text-center space-y-4">
                <p id="qris-amount" class="text-2xl font-bold text-emerald-700"></p>

                <div id="inline-payment-qris" class="hidden">
                    <canvas id="qris-canvas" class="mx-auto rounded-xl border border-emerald-100 bg-white p-2"></canvas>
                    <p class="text-sm text-gray-600 mt-4">Buka aplikasi bank atau e-wallet, pilih Scan QRIS, lalu scan kode di atas.</p>
                </div>

                <div id="inline-payment-va" class="hidden">
                    <div class="rounded-xl border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Bank</p>
                        <p id="va-bank-label" class="text-sm font-semibold text-slate-800 mb-3"></p>
                        <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Nomor Virtual Account</p>
                        <p id="va-number" class="text-2xl font-mono font-bold tracking-wider text-emerald-800"></p>
                        <button type="button" id="copy-va-number" class="mt-3 text-sm text-emerald-700 font-semibold hover:underline">Salin nomor VA</button>
                    </div>
                    <p class="text-sm text-gray-600 mt-4">Transfer tepat sesuai nominal ke nomor VA di atas melalui m-banking atau ATM.</p>
                </div>

                <div id="inline-payment-redirect" class="hidden space-y-3">
                    <p class="text-sm text-gray-600">Selesaikan pembayaran di halaman {{ config('payment.label') }}. Status di halaman ini diperbarui otomatis.</p>
                    <div id="inline-payment-checkout-wrap">
                        <a id="inline-payment-checkout-link" href="#" class="app-btn inline-block px-6 py-2.5 rounded-xl font-semibold">Buka halaman pembayaran</a>
                    </div>
                </div>

                <p id="qris-status" class="text-sm text-amber-700 font-medium">Menunggu pembayaran...</p>

                <button type="button" id="change-payment-method" class="text-sm text-emerald-700 font-medium hover:underline">Ganti metode pembayaran</button>
            </div>
        </div>

        {{-- Progress --}}
        <div id="wizard-progress-view" class="hidden p-6" data-deploy-progress-root>
            <div class="text-center mb-6">
                <div class="relative w-20 h-20 mx-auto mb-3">
                    <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                        <circle cx="40" cy="40" r="34" fill="none" stroke="#d1fae5" stroke-width="6"/>
                        <circle data-progress-ring cx="40" cy="40" r="34" fill="none" stroke="#059669" stroke-width="6"
                            stroke-dasharray="213.6" stroke-dashoffset="213.6" stroke-linecap="round" class="transition-all duration-500"/>
                    </svg>
                    <span data-progress-pct class="absolute inset-0 flex items-center justify-center text-lg font-bold text-emerald-700">0%</span>
                </div>
                <h3 class="font-bold text-gray-900">Deploying RDM...</h3>
                <p data-progress-label class="text-sm text-muted mt-1">Memulai...</p>
            </div>
            <div data-progress-steps class="space-y-2 text-sm max-h-48 overflow-y-auto"></div>
            <div data-progress-done class="hidden mt-6 text-center space-y-3">
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                    Deploy berhasil! Notifikasi email telah dikirim.
                </div>
                <a data-progress-url href="#" target="_blank" class="app-btn inline-block px-6 py-2.5 rounded-xl font-semibold">Buka RDM</a>
                <a href="{{ route('user.dashboard') }}" class="block text-sm text-emerald-700 font-medium hover:underline">Ke Dashboard Panel →</a>
            </div>
            <div data-progress-error class="hidden mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm"></div>
        </div>
    </div>
</div>
