@extends('layouts.landing')

@section('title', 'Bayar QRIS — '.$instance->fqdn)

@section('content')
    <section class="pt-28 pb-20 px-4 sm:px-6 min-h-[70vh]">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-2xl shadow-xl border border-emerald-100 overflow-hidden">
                <div class="px-6 pt-6 pb-4 border-b border-emerald-100 text-center">
                    <h1 class="text-xl font-bold text-gray-900">Scan QRIS</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $instance->fqdn }}</p>
                    <p class="text-lg font-bold text-emerald-700 mt-2">Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                </div>

                <div class="p-6 text-center space-y-4">
                    <canvas id="renewal-qris-canvas" class="mx-auto rounded-xl border border-gray-100"></canvas>
                    <p class="text-sm text-gray-600">Scan dengan aplikasi bank atau e-wallet yang mendukung QRIS.</p>
                    <p id="renewal-qris-status" class="text-sm text-amber-700 font-medium">Menunggu pembayaran...</p>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script type="module">
import QRCode from 'qrcode';

const qrString = @json($payment->meta['qr_string'] ?? '');
const statusUrl = @json(route('renewal.qris.status', ['subdomain' => $subdomain, 'ref' => $payment->external_id]));

if (qrString) {
    QRCode.toCanvas(document.getElementById('renewal-qris-canvas'), qrString, {
        width: 260,
        margin: 2,
        errorCorrectionLevel: 'M',
    });
}

const poll = async () => {
    try {
        const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        if (data.status === 'paid' && data.redirect_url) {
            document.getElementById('renewal-qris-status').textContent = 'Pembayaran berhasil! Mengalihkan...';
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
