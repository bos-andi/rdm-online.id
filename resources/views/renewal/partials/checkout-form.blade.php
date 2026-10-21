<div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/80 border border-emerald-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-emerald-100 bg-emerald-50/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Ringkasan pesanan</p>
                <h1 class="text-lg font-bold text-gray-900 mt-1">{{ $instance->madrasah_name }}</h1>
                <p class="text-sm text-gray-500">{{ $instance->fqdn }}</p>
                <p class="text-xs text-gray-500 mt-1">Paket {{ $pkg['label'] ?? $instance->package }} · +{{ $pkg['days'] ?? 30 }} hari</p>
            </div>
            <div class="text-right min-w-[9rem]">
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-4 text-gray-600">
                        <span>Harga paket</span>
                        <span id="summary-package">Rp{{ number_format($amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between gap-4 text-gray-600">
                        <span>Biaya admin</span>
                        <span id="summary-admin-fee" class="text-gray-400">—</span>
                    </div>
                    <div class="flex justify-between gap-4 pt-2 border-t border-emerald-200 font-bold text-emerald-700">
                        <span>Total bayar</span>
                        <span id="summary-total">Rp{{ number_format($amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('renewal.pay', $instance->subdomain) }}"
            id="checkout-form"
            data-package-amount="{{ $amount }}"
            data-admin-fees='@json($adminFees ?? [])'
        >
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">

            <h2 class="text-sm font-semibold text-gray-800 mb-1">Pilih metode pembayaran</h2>
            <p class="text-xs text-gray-500 mb-4">Biaya admin bervariasi per metode · diproses aman melalui {{ config('payment.label') }}</p>

            <div class="space-y-6">
                @foreach ($methodGroups as $group => $methods)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-3">{{ $group }}</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($methods as $method)
                                @php($methodAdminFee = $adminFees[$method['code']] ?? 0)
                                <label
                                    class="payment-method-card relative flex items-start gap-3 p-4 rounded-xl border-2 border-gray-200 cursor-pointer transition-all hover:border-emerald-300 hover:bg-emerald-50/30 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50"
                                >
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $method['code'] }}"
                                        data-admin-fee="{{ $methodAdminFee }}"
                                        data-fee-percent="{{ $method['fee_percent'] ?? 0 }}"
                                        data-fee-flat="{{ $method['fee_flat'] ?? $methodAdminFee }}"
                                        data-fee-customer="{{ ($method['fee_bearer'] ?? '') === 'customer' ? '1' : '0' }}"
                                        class="mt-1 text-emerald-600 border-emerald-300 focus:ring-emerald-500"
                                        @checked(old('payment_method') === $method['code'])
                                        required
                                    >
                                    <span class="min-w-0">
                                        <span class="block font-semibold text-gray-900 text-sm">{{ $method['label'] }}</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">{{ $method['description'] ?? '' }}</span>
                                        @if ($methodAdminFee > 0)
                                            <span class="block text-xs text-amber-700 mt-1 font-medium">
                                                + Rp{{ number_format($methodAdminFee, 0, ',', '.') }} biaya admin
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
                <a
                    href="{{ route('renewal.show', $instance->subdomain) }}"
                    class="flex-1 text-center py-3 rounded-xl border border-gray-200 text-gray-600 font-medium hover:bg-gray-50 transition-colors"
                >
                    &larr; Kembali
                </a>
                <button type="submit" class="flex-1 app-btn font-semibold py-3 rounded-xl" id="pay-button" disabled>
                    Bayar Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<p class="text-center text-gray-400 text-xs mt-4">
    Total bayar = harga paket + biaya admin metode yang dipilih. Setelah klik Bayar, QRIS/VA tampil langsung atau Anda diarahkan ke halaman pembayaran {{ config('payment.label') }}.
</p>
