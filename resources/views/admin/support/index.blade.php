@extends('layouts.admin')

@section('title', 'Support Management')

@section('content')
    <x-admin.page-header title="Support Management" subtitle="Berdasarkan status deploy RDM" />

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="admin-stat-card text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $incoming }}</p>
            <p class="text-slate-500 text-sm mt-1">Deploy Gagal</p>
        </div>
        <div class="admin-stat-card text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $processing }}</p>
            <p class="text-slate-500 text-sm mt-1">Sedang Deploy</p>
        </div>
        <div class="admin-stat-card text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $resolved }}</p>
            <p class="text-slate-500 text-sm mt-1">RDM Aktif</p>
        </div>
    </div>

    @if ($failedInstances->isNotEmpty())
        <div class="admin-card overflow-hidden mb-6">
            <div class="admin-card-header px-4 py-3 font-semibold text-rose-700">Deploy Gagal — Perlu Tindakan</div>
            <table class="w-full text-sm">
                <thead class="admin-card-header">
                    <tr>
                        <th class="p-3 text-left font-semibold text-slate-700">Subdomain</th>
                        <th class="p-3 text-left font-semibold text-slate-700">PIC</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Error</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Waktu</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($failedInstances as $rdm)
                        <tr class="border-t border-slate-100">
                            <td class="p-3 font-medium">{{ $rdm->fqdn }}</td>
                            <td class="p-3 text-slate-500">{{ $rdm->pic_email }}</td>
                            <td class="p-3 text-rose-600 text-xs">{{ $rdm->error_message ?? '—' }}</td>
                            <td class="p-3 text-slate-400 text-xs">{{ $rdm->updated_at->diffForHumans() }}</td>
                            <td class="p-3">
                                @if ($rdm->canRetryDeploy())
                                    <button
                                        type="button"
                                        class="text-xs text-amber-700 hover:underline font-medium"
                                        data-deploy-retry
                                        data-instance-id="{{ $rdm->id }}"
                                        data-fqdn="{{ $rdm->fqdn }}"
                                        data-retry-url="{{ route('admin.rdm.retry', $rdm) }}"
                                        data-status-url="{{ route('admin.rdm.deploy-status', $rdm) }}"
                                    >
                                        Deploy Ulang
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($deployingInstances->isNotEmpty())
        <div class="admin-card overflow-hidden">
            <div class="admin-card-header px-4 py-3 font-semibold text-amber-700">Sedang Deploy</div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($deployingInstances as $rdm)
                        <tr class="border-t border-slate-100">
                            <td class="p-3 font-medium">{{ $rdm->fqdn }}</td>
                            <td class="p-3 text-slate-500">{{ $rdm->current_step ?? '—' }}</td>
                            <td class="p-3">{{ $rdm->progress }}%</td>
                            <td class="p-3 text-slate-400 text-xs">{{ $rdm->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-deploy-progress-modal
        id="admin-deploy-progress-modal"
        title="Deploy Ulang RDM"
        subtitle="Proses deploy berjalan di server VPS."
        done-message="Deploy berhasil! RDM siap digunakan."
        :dashboard-url="route('admin.rdm')"
        dashboard-label="Kembali ke Manajemen RDM"
    />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initDeployRetryButtons === 'function') {
        window.initDeployRetryButtons({
            modalId: 'admin-deploy-progress-modal',
            reloadOnClose: true,
        });
    }
});
</script>
@endpush
