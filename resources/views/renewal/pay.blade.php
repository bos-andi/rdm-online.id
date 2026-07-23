@extends('layouts.landing')

@section('title', 'Bayar — '.$instance->fqdn)

@section('content')
    <section class="pt-28 pb-20 px-4 sm:px-6 min-h-[70vh]">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-2xl shadow-xl border border-emerald-100 overflow-hidden">
                <div class="px-6 pt-6 pb-4 border-b border-emerald-100 text-center">
                    @if (($presentation['payment_mode'] ?? '') === 'va_inline')
                        <h1 class="text-xl font-bold text-gray-900">Transfer Virtual Account</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ $presentation['bank_label'] ?? 'Virtual Account' }}</p>
                    @else
                        <h1 class="text-xl font-bold text-gray-900">Scan QRIS</h1>
                    @endif
                    <p class="text-sm text-gray-500 mt-1">{{ $instance->fqdn }}</p>
                    <p class="text-lg font-bold text-emerald-700 mt-2">Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                </div>

                <div class="p-6 text-center space-y-4">
                    @if (($presentation['payment_mode'] ?? '') === 'va_inline')
                        <div class="rounded-xl border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Nomor Virtual Account</p>
                            <p id="renewal-va-number" class="text-2xl font-mono font-bold tracking-wider text-emerald-800">{{ $presentation['va_number'] }}</p>
                            <button type="button" id="renewal-copy-va" class="mt-3 text-sm text-emerald-700 font-semibold hover:underline">Salin nomor VA</button>
                        </div>
                        <p class="text-sm text-gray-600">Transfer tepat sesuai nominal ke nomor VA di atas melalui m-banking atau ATM.</p>
                    @else
                        <canvas id="renewal-qris-canvas" class="mx-auto rounded-xl border border-gray-100"></canvas>
                        <p class="text-sm text-gray-600">Scan dengan aplikasi bank atau e-wallet yang mendukung QRIS.</p>
                    @endif
                    <p id="renewal-pay-status" class="text-sm text-amber-700 font-medium">Menunggu pembayaran...</p>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script type="module">
import QRCode from 'qrcode';

const presentation = @json($presentation);
const statusUrl = @json(route('renewal.pay.status', ['subdomain' => $subdomain, 'ref' => $payment->external_id]));

if (presentation.payment_mode === 'qris_inline' && presentation.qr_string) {
    QRCode.toCanvas(document.getElementById('renewal-qris-canvas'), presentation.qr_string, {
        width: 260,
        margin: 2,
        errorCorrectionLevel: 'M',
    });
}

document.getElementById('renewal-copy-va')?.addEventListener('click', async () => {
    const number = document.getElementById('renewal-va-number')?.textContent?.trim();
    if (!number) return;
    try {
        await navigator.clipboard.writeText(number);
        document.getElementById('renewal-copy-va').textContent = 'Tersalin!';
    } catch {
        window.prompt('Salin nomor VA:', number);
    }
});

const poll = async () => {
    try {
        const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        if (data.status === 'paid' && data.redirect_url) {
            document.getElementById('renewal-pay-status').textContent = 'Pembayaran berhasil! Mengalihkan...';
            window.location.href = data.redirect_url;
        }
    } catch {
        // retry
    }
};

poll();
setInterval(poll, 2000);
</script>
@endpush
