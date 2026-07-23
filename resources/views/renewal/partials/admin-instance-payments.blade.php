<div class="admin-card overflow-hidden">
    <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">
        Transaksi Instance Ini
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($instancePayments as $payment)
            <div class="p-4 text-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-medium text-slate-800">{{ $payment->channelLabel() }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    <x-admin.badge
                        :status="$payment->statusLabel()"
                        :variant="$payment->statusVariant()"
                    />
                </div>
                <p class="font-semibold text-emerald-700 mt-2">
                    Rp{{ number_format($payment->amount, 0, ',', '.') }}
                </p>
                @if ($payment->status === 'pending' && $payment->invoice_url)
                    <a href="{{ $payment->invoice_url }}" target="_blank" rel="noopener" class="inline-block text-xs text-emerald-700 hover:underline mt-2">
                        Buka invoice Xendit
                    </a>
                @endif
            </div>
        @empty
            <p class="p-6 text-center text-slate-400 text-sm">Belum ada transaksi untuk instance ini.</p>
        @endforelse
    </div>
    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50">
        <a href="{{ route('admin.billing') }}" class="text-sm font-medium text-emerald-700 hover:underline">
            Lihat semua transaksi &rarr;
        </a>
    </div>
</div>
