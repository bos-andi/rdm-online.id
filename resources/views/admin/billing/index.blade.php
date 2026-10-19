@extends('layouts.admin')

@section('title', 'Billing & Pembayaran')

@section('content')
    <x-admin.page-header title="Billing & Pembayaran" subtitle="Transaksi {{ config('payment.label') }} dan langganan RDM" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">Pendapatan Bulan Ini</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">Rp{{ number_format($paidThisMonthAmount, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $paidThisMonth }} transaksi lunas</p>
        </div>
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">Menunggu Pembayaran</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $pendingPayments }}</p>
            <p class="text-xs text-slate-400 mt-1">Invoice belum dibayar</p>
        </div>
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">Total Terkumpul</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">Rp{{ number_format($totalPaidAmount, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalPayments }} transaksi</p>
        </div>
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">Langganan Perlu Perhatian</p>
            <p class="text-2xl font-bold text-rose-600 mt-1">{{ $pending }}</p>
            <p class="text-xs text-slate-400 mt-1">Expired / deploy / hampir habis</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <div class="lg:col-span-2 admin-card overflow-hidden">
            <div class="admin-card-header px-4 py-3 font-semibold text-slate-700 flex items-center justify-between">
                <span>Riwayat Pembayaran</span>
                <span class="text-xs font-normal text-slate-400">via {{ config('payment.label') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-t border-slate-100">
                            <th class="p-3 text-left font-semibold text-slate-700">Tanggal</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Metode</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Jumlah</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 text-slate-500 whitespace-nowrap">
                                    {{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="p-3">
                                    <p class="font-medium text-slate-800">{{ $payment->rdmInstance?->madrasah_name ?? '—' }}</p>
                                    <p class="text-xs text-emerald-700">{{ $payment->rdmInstance?->fqdn }}</p>
                                    @if ($payment->payer_email)
                                        <p class="text-xs text-slate-400">{{ $payment->payer_email }}</p>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <p>{{ $payment->channelLabel() }}</p>
                                    <p class="text-xs text-slate-400">{{ config('deploy.packages.'.$payment->package.'.label') ?? $payment->package }}</p>
                                </td>
                                <td class="p-3 font-medium whitespace-nowrap">
                                    Rp{{ number_format($payment->amount, 0, ',', '.') }}
                                    @if (($payment->meta['admin_fee'] ?? 0) > 0)
                                        <p class="text-xs font-normal text-slate-400">
                                            paket Rp{{ number_format($payment->meta['package_amount'] ?? ($payment->amount - $payment->meta['admin_fee']), 0, ',', '.') }}
                                            + admin Rp{{ number_format($payment->meta['admin_fee'], 0, ',', '.') }}
                                        </p>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <x-admin.badge
                                        :status="$payment->statusLabel()"
                                        :variant="$payment->statusVariant()"
                                    />
                                    @if ($payment->status === 'pending' && $payment->invoice_url)
                                        <a href="{{ $payment->invoice_url }}" target="_blank" rel="noopener" class="block text-xs text-emerald-700 hover:underline mt-1">
                                            Buka invoice
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">Belum ada transaksi pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="admin-card p-5">
                <h3 class="font-semibold text-slate-900 mb-3">Metode {{ config('payment.label') }} Aktif</h3>
                @if (! empty($gatewayChannels['synced_at']))
                    <p class="text-xs text-slate-400 mb-3">Sync {{ \Illuminate\Support\Carbon::parse($gatewayChannels['synced_at'])->diffForHumans() }}</p>
                @endif
                @if (! empty($gatewayChannels['error']))
                    <p class="text-xs text-rose-600 mb-3">Gagal sync: {{ $gatewayChannels['error'] }}</p>
                @endif
                <div class="space-y-3 text-sm">
                    @forelse ($gatewayChannels['groups'] ?? [] as $group => $channels)
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 mb-1">{{ $group }}</p>
                            <p class="text-slate-700">{{ implode(', ', $channels) }}</p>
                        </div>
                    @empty
                        <p class="text-slate-400">Belum ada channel aktif.</p>
                    @endforelse
                </div>
            </div>

            <div class="admin-card p-5">
                <h3 class="font-semibold text-slate-900 mb-3">Paket Aktif</h3>
                <div class="space-y-2 text-sm">
                    @forelse ($byPackage as $package => $count)
                        <div class="flex justify-between">
                            <span class="text-slate-600">{{ config('deploy.packages.'.$package.'.label') ?? $package }}</span>
                            <span class="font-medium">{{ $count }} RDM</span>
                        </div>
                    @empty
                        <p class="text-slate-400">Belum ada langganan aktif.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">
            Daftar Langganan RDM
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-t border-slate-100">
                    <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Paket</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Berakhir</th>
                    <th class="p-3 text-left font-semibold text-slate-700">PIC</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($instances as $rdm)
                    @php($pkg = config('deploy.packages.'.$rdm->package))
                    <tr class="border-t border-slate-100">
                        <td class="p-3">
                            <p class="font-medium">{{ $rdm->madrasah_name }}</p>
                            <p class="text-xs text-emerald-700">{{ $rdm->fqdn }}</p>
                        </td>
                        <td class="p-3">
                            {{ $pkg['label'] ?? $rdm->package }}
                            <span class="text-slate-400">· Rp{{ number_format($pkg['price'] ?? 0, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-3 text-slate-500">{{ $rdm->expires_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="p-3 text-slate-500">{{ $rdm->pic_email }}</td>
                        <td class="p-3">
                            <x-admin.badge
                                :status="ucfirst($rdm->status)"
                                :variant="match($rdm->status) {
                                    'active' => 'success',
                                    'failed', 'expired', 'suspended' => 'danger',
                                    'deploying' => 'warning',
                                    default => 'default'
                                }"
                            />
                        </td>
                        <td class="p-3">
                            @if (in_array($rdm->status, ['expired', 'suspended', 'active'], true))
                                <a href="{{ route('admin.billing.checkout', $rdm->subdomain) }}" class="text-xs font-medium text-emerald-700 hover:underline whitespace-nowrap">
                                    Buka checkout
                                </a>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">Belum ada data langganan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
