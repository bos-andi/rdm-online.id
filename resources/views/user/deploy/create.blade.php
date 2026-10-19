@extends('layouts.app')

@section('title', 'Buat RDM Baru')

@section('content')
    <x-user.page-header title="Buat RDM Baru" subtitle="Isi data madrasah, pilih paket, bayar — deploy otomatis setelah lunas." />

    @if ($failedRetryable->isNotEmpty())
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 max-w-xl mx-auto">
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

        <x-deploy-progress-modal
            id="user-deploy-progress-modal"
            title="Deploy Ulang RDM"
            subtitle="Mohon tunggu, proses deploy berjalan di server."
            :dashboard-url="route('user.rdm')"
            dashboard-label="Kembali ke RDM Saya"
        />
    @else
        @include('deploy.partials.wizard', [
            'user' => $user,
            'isGuest' => false,
            'paymentMethods' => $paymentMethods,
            'adminFees' => $adminFees,
            'packagePrices' => $packagePrices,
            'pricingDisplay' => $pricingDisplay,
        ])
    @endif

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
