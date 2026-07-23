@extends('layouts.app')

@section('title', 'Langganan Habis')

@section('content')
    <x-user.page-header title="Langganan Habis" subtitle="Akses RDM Anda telah dinonaktifkan" />

    <div class="app-card p-6 mb-6 border-amber-200 bg-amber-50">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-xl bg-amber-100 text-amber-700 shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-semibold text-amber-900">Masa langganan telah berakhir</h2>
                <p class="text-sm text-amber-800 mt-1">
                    Panel dashboard, monitoring, backup, dan aplikasi RDM tidak dapat diakses sampai langganan diperpanjang.
                    Halaman billing dan support tetap tersedia.
                </p>
            </div>
        </div>
    </div>

    @if ($instances->isNotEmpty())
        <div class="space-y-4">
            @foreach ($instances as $rdm)
                @php($pkg = config('deploy.packages.'.$rdm->package))
                @php($expired = $subscriptions->isExpired($rdm))
                <div class="app-card p-5">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <div>
                            <p class="font-semibold text-emerald-900">{{ $rdm->madrasah_name }}</p>
                            <p class="text-muted text-sm">{{ $rdm->fqdn }}</p>
                        </div>
                        <x-user.badge :status="$subscriptions->statusLabel($rdm)" />
                    </div>
                    <div class="flex flex-wrap justify-between items-center gap-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-800">Paket {{ $pkg['label'] ?? $rdm->package }}</p>
                            @if ($rdm->expires_at)
                                <p class="text-muted">Berakhir {{ $rdm->expires_at->translatedFormat('d M Y') }}</p>
                            @endif
                        </div>
                        @unless ($expired)
                            <a href="https://{{ $rdm->fqdn }}" target="_blank" rel="noopener" class="app-btn-sm rounded">Buka RDM</a>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('user.billing') }}" class="app-btn inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold">
            <i data-lucide="credit-card" class="w-4 h-4"></i>
            Lihat Billing
        </a>
        @if ($instances->first())
            <a href="{{ route('renewal.show', $instances->first()->subdomain) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold border border-emerald-600 text-emerald-700 hover:bg-emerald-50">
                Bayar Perpanjang (Tanpa Login)
            </a>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
