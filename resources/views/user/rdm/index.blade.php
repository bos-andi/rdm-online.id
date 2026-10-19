@extends('layouts.app')

@section('title', 'RDM Saya')

@section('content')
    <x-user.page-header title="RDM Saya" />

    @if (session('status'))
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($instances->isEmpty())
        <div class="app-card p-8 text-center">
            <p class="text-muted mb-4">Belum ada RDM yang di-deploy.</p>
            <a href="{{ route('user.deploy') }}" class="app-btn inline-block px-6 py-2.5 rounded-lg font-semibold">Buat RDM Baru</a>
        </div>
    @else
        <div class="app-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="app-card-header">
                    <tr>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Nama</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Domain</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Paket</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Status</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($instances as $rdm)
                        @php($accessible = $subscriptions->isAccessible($rdm))
                        <tr class="border-t app-divider">
                            <td class="p-3 font-medium">{{ $rdm->madrasah_name }}</td>
                            <td class="p-3 text-emerald-600">{{ $rdm->fqdn }}</td>
                            <td class="p-3">{{ config('deploy.packages.'.$rdm->package.'.label') }}</td>
                            <td class="p-3">
                                @php($statusLabel = $subscriptions->statusLabel($rdm))
                                @php($badgeVariant = match (true) {
                                    $rdm->status === 'pending_payment' => 'warning',
                                    $rdm->status === 'failed' => 'danger',
                                    $accessible => 'success',
                                    default => 'default',
                                })
                                <x-user.badge :status="$statusLabel" :variant="$badgeVariant" />
                                @if ($rdm->status === 'failed' && $rdm->error_message)
                                    <p class="text-xs text-rose-500 mt-1 max-w-xs">{{ Str::limit($rdm->error_message, 80) }}</p>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($accessible)
                                    <a href="https://{{ $rdm->fqdn }}" target="_blank" rel="noopener" class="app-btn-sm rounded">Buka</a>
                                @elseif ($rdm->canResumePayment())
                                    <a
                                        href="{{ route('user.deploy', ['resume' => $rdm->id]) }}"
                                        class="app-btn-sm rounded bg-amber-500 hover:bg-amber-600 text-white"
                                    >
                                        Lanjutkan Pembayaran
                                    </a>
                                @elseif ($rdm->canRetryDeploy())
                                    <button
                                        type="button"
                                        class="app-btn-sm rounded bg-amber-500 hover:bg-amber-600 text-white"
                                        data-deploy-retry
                                        data-instance-id="{{ $rdm->id }}"
                                        data-fqdn="{{ $rdm->fqdn }}"
                                        data-retry-url="{{ url('/api/deploy/'.$rdm->id.'/retry') }}"
                                        data-status-url="{{ url('/api/deploy/'.$rdm->id) }}"
                                    >
                                        Deploy Ulang
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-deploy-progress-modal
        id="user-deploy-progress-modal"
        title="Deploy Ulang RDM"
        subtitle="Mohon tunggu, proses deploy berjalan di server."
        :dashboard-url="route('user.rdm')"
        dashboard-label="Kembali ke RDM Saya"
    />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initDeployRetryButtons === 'function') {
        window.initDeployRetryButtons({
            modalId: 'user-deploy-progress-modal',
            reloadOnClose: true,
        });
    }
});
</script>
@endpush
