@extends('layouts.app')

@section('title', 'Billing & Pembayaran')

@section('content')
    <x-user.page-header title="Billing & Pembayaran" />

    @if ($instances->isEmpty())
        <div class="app-card p-8 text-center text-muted">
            Belum ada langganan RDM.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($instances as $rdm)
                @php($pkg = config('deploy.packages.'.$rdm->package))
                @php($expired = $subscriptions->isExpired($rdm))
                @php($daysLeft = $subscriptions->daysRemaining($rdm))
                <div @class([
                    'app-card p-5',
                    'border-amber-200 bg-amber-50/40' => $expired,
                ])>
                    <div class="flex justify-between items-start gap-4 mb-4">
                        <div>
                            <p class="font-semibold text-emerald-900">{{ $rdm->madrasah_name }}</p>
                            <p class="text-muted text-sm">{{ $rdm->fqdn }}</p>
                        </div>
                        <x-user.badge :status="$subscriptions->statusLabel($rdm)" />
                    </div>
                    <div class="flex flex-wrap justify-between items-center gap-4">
                        <div>
                            <p class="font-medium text-gray-800">Paket {{ $pkg['label'] ?? $rdm->package }}</p>
                            <p class="text-muted text-sm">Rp{{ number_format($pkg['price'] ?? 0, 0, ',', '.') }}/{{ $pkg['period'] ?? 'bulan' }}</p>
                        </div>
                        <div class="text-sm text-right">
                            @if ($rdm->expires_at)
                                <p @class(['font-medium', 'text-rose-600' => $expired, 'text-emerald-700' => ! $expired && $daysLeft !== null && $daysLeft <= 7])>
                                    @if ($expired)
                                        Berakhir {{ $rdm->expires_at->translatedFormat('d M Y') }}
                                    @elseif ($daysLeft !== null)
                                        Sisa {{ $daysLeft }} hari (hingga {{ $rdm->expires_at->translatedFormat('d M Y') }})
                                    @endif
                                </p>
                            @elseif ($rdm->deployed_at)
                                <p class="text-muted">Aktif sejak {{ $rdm->deployed_at->translatedFormat('d M Y') }}</p>
                            @endif
                        </div>
                    </div>
                    @if ($expired)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-amber-800">
                                Langganan habis — aplikasi RDM tidak dapat diakses.
                            </p>
                            <a href="{{ route('renewal.show', $rdm->subdomain) }}" class="app-btn-sm rounded font-semibold">
                                Bayar Perpanjang
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
