@extends('layouts.landing')

@section('title', 'Deploy RDM — RDM-ONLINE.ID')

@section('content')
    <section class="pt-28 pb-20 px-4 sm:px-6 min-h-[70vh]">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Deploy RDM Madrasah</h1>
            <p class="text-gray-600 mt-3">
                @if ($isGuest)
                    Isi data madrasah & buat akun — bayar via Xendit, deploy otomatis setelah lunas.
                @else
                    Halo {{ $user->name }}, deploy RDM baru untuk madrasah Anda.
                @endif
            </p>

            @if (! $isGuest && $failedRetryable->isNotEmpty())
                <div class="mt-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-left max-w-lg mx-auto">
                    <p class="text-sm font-semibold text-amber-900">Deploy sebelumnya gagal</p>
                    <p class="text-sm text-amber-800 mt-1">
                        Pembayaran Anda sudah tercatat. Tidak perlu bayar lagi — klik Deploy Ulang.
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

            <button type="button" id="open-deploy-wizard" class="landing-btn-primary inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-base font-semibold shadow-lg shadow-emerald-200 mt-8" @if (! $isGuest && $failedRetryable->isNotEmpty()) disabled title="Selesaikan deploy gagal terlebih dahulu" @endif>
                <i data-lucide="rocket" class="w-5 h-5"></i>
                Mulai Deploy
            </button>
            @if ($isGuest)
                <p class="text-sm text-gray-500 mt-4">
                    Sudah punya akun?
                    <a href="{{ route('user.login') }}" class="text-emerald-700 font-medium hover:underline">Masuk di sini</a>
                </p>
            @endif
        </div>
    </section>

    @include('deploy.partials.wizard', [
        'user' => $user,
        'isGuest' => $isGuest,
        'paymentMethods' => $paymentMethods,
        'adminFees' => $adminFees,
        'packagePrices' => $packagePrices,
        'pricingDisplay' => $pricingDisplay ?? [],
    ])

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
    const params = new URLSearchParams(window.location.search);
    const monitorId = params.get('monitor');
    const paidRef = params.get('paid');

    const openDeployMonitor = (instanceId) => {
        document.body.classList.add('overflow-hidden');
        window.__rdmMonitorInstanceId = instanceId;
        document.getElementById('open-deploy-wizard')?.click();
    };

    if (monitorId) {
        openDeployMonitor(monitorId);
        return;
    }

    if (paidRef) {
        fetch(`/api/deploy/payment/${encodeURIComponent(paidRef)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then((res) => (res.ok ? res.json() : Promise.reject()))
            .then((data) => {
                if (data.instance_id) {
                    openDeployMonitor(data.instance_id);
                }
            })
            .catch(() => {
                document.getElementById('open-deploy-wizard')?.click();
            });
        return;
    }

    if (window.__rdmResumeInstance) {
        document.body.classList.add('overflow-hidden');
        return;
    }

    if (typeof window.initDeployRetryButtons === 'function') {
        window.initDeployRetryButtons({
            modalId: 'user-deploy-progress-modal',
            reloadOnClose: false,
        });
    }

    @if (request()->routeIs('deploy') && ! request()->has('monitor') && ! request()->has('paid') && ! request()->has('resume') && (empty($failedRetryable) || $failedRetryable->isEmpty()))
    document.body.classList.add('overflow-hidden');
    document.getElementById('open-deploy-wizard')?.click();
    @endif
});
</script>
@endpush
