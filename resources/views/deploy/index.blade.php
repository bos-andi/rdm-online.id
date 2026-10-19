@extends('layouts.landing')

@section('title', 'Deploy RDM — RDM-ONLINE.ID')

@section('content')
    <section class="pt-28 pb-20 px-4 sm:px-6 min-h-[70vh] bg-gradient-to-b from-emerald-50 to-white">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Deploy RDM Madrasah</h1>
            <p class="text-gray-600 mt-3">
                @if ($isGuest)
                    Isi data madrasah & buat akun — bayar via {{ config('payment.label') }}, deploy otomatis setelah lunas.
                @else
                    Halo {{ $user->name }}, deploy RDM baru untuk madrasah Anda.
                @endif
            </p>
            @if ($isGuest)
                <p class="text-sm text-gray-500 mt-2">
                    Sudah punya akun?
                    <a href="{{ route('user.login') }}" class="text-emerald-700 font-medium hover:underline">Masuk di sini</a>
                </p>
            @endif

            @if (! $isGuest && $failedRetryable->isNotEmpty())
                <div class="mt-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-left max-w-lg mx-auto">
                    <p class="text-sm font-semibold text-amber-900">Deploy sebelumnya gagal</p>
                    <p class="text-sm text-amber-800 mt-1">
                        Pembayaran Anda sudah tercatat. Tidak perlu bayar lagi — klik Deploy Ulang.
                        Deploy RDM baru bisa dilakukan setelah deploy ini selesai.
                    </p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($failedRetryable as $rdm)
                            <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="font-medium text-gray-800">{{ $rdm->fqdn }}</span>
                                <button
                                    type="button"
                                    class="text-amber-800 font-semibold hover:underline"
                                    data-deploy-retry
                                    data-instance-id="{{ $rdm->id }}"
                                    data-fqdn="{{ $rdm->fqdn }}"
                                    data-retry-url="{{ url('/api/deploy/'.$rdm->id.'/retry') }}"
                                    data-status-url="{{ url('/api/deploy/'.$rdm->id) }}"
                                >
                                    Deploy Ulang
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if ($isGuest || $failedRetryable->isEmpty())
            <div class="mt-10">
                @include('deploy.partials.wizard', [
                    'user' => $user,
                    'isGuest' => $isGuest,
                    'paymentMethods' => $paymentMethods,
                    'adminFees' => $adminFees,
                    'packagePrices' => $packagePrices,
                    'pricingDisplay' => $pricingDisplay ?? [],
                ])
            </div>
        @endif
    </section>

    @if ($resumeInstance)
        @php
            $resumePayload = [
                'id' => $resumeInstance->id,
                'subdomain' => $resumeInstance->subdomain,
                'fqdn' => $resumeInstance->fqdn,
                'madrasah_name' => $resumeInstance->madrasah_name,
                'jenjang' => $resumeInstance->jenjang,
                'package' => $resumeInstance->package,
                'email' => $user->email,
            ];
        @endphp
        <script>
            window.__rdmResumeInstance = @json($resumePayload);
        </script>
    @endif

    @if (! $isGuest && $failedRetryable->isNotEmpty())
        <x-deploy-progress-modal
            id="user-deploy-progress-modal"
            title="Deploy Ulang RDM"
            subtitle="Mohon tunggu, proses deploy berjalan di server."
            :dashboard-url="route('user.rdm')"
            dashboard-label="Kembali ke RDM Saya"
        />
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initDeployRetryButtons === 'function') {
        window.initDeployRetryButtons({
            modalId: 'user-deploy-progress-modal',
            reloadOnClose: false,
        });
    }
});
</script>
@endpush
