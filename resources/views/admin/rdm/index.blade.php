@extends('layouts.admin')

@section('title', 'Manajemen RDM')

@section('content')
    <x-admin.page-header title="Manajemen RDM" subtitle="Deploy, rebuild, suspend, hapus permanen, dan kelola semua RDM" />

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="admin-card p-4 mb-6 text-sm text-slate-600 border-amber-100 bg-amber-50/50">
        <p class="font-medium text-slate-800">Kebijakan penghapusan otomatis</p>
        <p class="mt-1">RDM <strong>expired</strong> mendapat masa tenggang {{ config('subscription.grace_days', 30) }} hari. Email peringatan dikirim {{ config('subscription.purge_warning_days', 7) }} hari sebelum hapus permanen. Setelah itu file, database, nginx & DNS dihapus otomatis.</p>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="p-4 border-b border-slate-200 text-sm text-slate-500">
            {{ $instances->where('status', 'active')->count() }} RDM aktif dari {{ $instances->count() }} total
        </div>
        <table class="w-full text-sm">
            <thead class="admin-card-header">
                <tr>
                    <th class="p-3 text-left font-semibold text-slate-700">Subdomain</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                    <th class="p-3 text-left font-semibold text-slate-700">PIC Email</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Paket</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Berakhir</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Progress</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($instances as $rdm)
                    @php($statusLabel = $subscriptions->statusLabel($rdm))
                    <tr class="border-t border-slate-100">
                        <td class="p-3 font-medium text-emerald-700">{{ $rdm->fqdn }}</td>
                        <td class="p-3">{{ $rdm->madrasah_name }}</td>
                        <td class="p-3 text-slate-500">{{ $rdm->pic_email }}</td>
                        <td class="p-3">{{ config('deploy.packages.'.$rdm->package.'.label') }}</td>
                        <td class="p-3 text-slate-500">
                            {{ $rdm->expires_at?->translatedFormat('d M Y') ?? '—' }}
                            @if ($rdm->status === 'expired' && $rdm->purge_at)
                                <p @class([
                                    'text-xs mt-1',
                                    'text-rose-600 font-medium' => $rdm->purgeWarningDue(),
                                    'text-amber-600' => ! $rdm->purgeWarningDue() && $rdm->purge_at->isFuture(),
                                    'text-slate-400' => $rdm->purge_at->isPast(),
                                ])>
                                    @if ($rdm->purge_at->isFuture())
                                        Hapus permanen: {{ $rdm->purge_at->translatedFormat('d M Y') }}
                                        ({{ $rdm->daysUntilPurge() }} hari)
                                    @else
                                        Menunggu purge otomatis
                                    @endif
                                </p>
                            @endif
                        </td>
                        <td class="p-3">{{ $rdm->progress }}%</td>
                        <td class="p-3">
                            <x-admin.badge
                                :status="$statusLabel"
                                :variant="match($rdm->status) {
                                    'active' => 'success',
                                    'failed' => 'danger',
                                    'deploying', 'destroying' => 'warning',
                                    'expired', 'suspended' => 'danger',
                                    default => 'default'
                                }"
                            />
                            @if ($rdm->error_message && in_array($rdm->status, ['expired', 'suspended', 'failed']))
                                <p class="text-xs text-slate-400 mt-1 max-w-[12rem]">{{ $rdm->error_message }}</p>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($rdm->status === 'active' && ! $subscriptions->isExpired($rdm))
                                    <button
                                        type="button"
                                        class="text-xs text-rose-700 hover:underline"
                                        data-open-deactivate
                                        data-fqdn="{{ $rdm->fqdn }}"
                                        data-action="{{ route('admin.rdm.deactivate', $rdm) }}"
                                    >
                                        Nonaktifkan
                                    </button>
                                @endif

                                @if (in_array($rdm->status, ['active', 'expired', 'suspended']))
                                    @php($needsReactivation = in_array($rdm->status, ['expired', 'suspended']) || ($rdm->status === 'active' && $subscriptions->isExpired($rdm)))
                                    <form method="POST" action="{{ route('admin.rdm.renew', $rdm) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-emerald-700 hover:underline">
                                            {{ $needsReactivation ? 'Aktifkan' : 'Perpanjang' }}
                                        </button>
                                    </form>
                                @endif

                                @if ($rdm->status === 'deploying')
                                    <button
                                        type="button"
                                        class="text-xs text-blue-700 hover:underline font-medium"
                                        data-deploy-monitor
                                        data-fqdn="{{ $rdm->fqdn }}"
                                        data-status-url="{{ route('admin.rdm.deploy-status', $rdm) }}"
                                        data-progress-title="Progress Deploy"
                                    >
                                        Lihat Progress
                                    </button>
                                @endif

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
                                @endif

                                @if ($rdm->status === 'destroying')
                                    <button
                                        type="button"
                                        class="text-xs text-rose-700 hover:underline font-medium"
                                        data-destroy-monitor
                                        data-fqdn="{{ $rdm->fqdn }}"
                                        data-status-url="{{ route('admin.rdm.destroy-status', $rdm) }}"
                                    >
                                        Lihat Progress
                                    </button>
                                @endif

                                @if ($rdm->canDestroy())
                                    <button
                                        type="button"
                                        class="text-xs text-rose-800 hover:underline font-semibold"
                                        data-open-destroy
                                        data-fqdn="{{ $rdm->fqdn }}"
                                        data-subdomain="{{ $rdm->subdomain }}"
                                        data-action="{{ route('admin.rdm.destroy', $rdm) }}"
                                    >
                                        Hapus
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400">Belum ada RDM terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-deploy-progress-modal
        id="admin-deploy-progress-modal"
        title="Deploy Ulang RDM"
        subtitle="Proses deploy berjalan di server VPS."
        done-message="Deploy berhasil! RDM siap digunakan."
        :dashboard-url="route('admin.rdm')"
        dashboard-label="Kembali ke Manajemen RDM"
    />

    <x-deploy-progress-modal
        id="admin-destroy-progress-modal"
        title="Menghapus RDM..."
        subtitle="Proses penghapusan berjalan di server VPS."
        done-message="RDM berhasil dihapus permanen (file, database, nginx, DNS, dan data panel)."
        :dashboard-url="route('admin.rdm')"
        dashboard-label="Kembali ke Manajemen RDM"
        :show-open-link="false"
        :step-labels="config('deploy.destroy_steps')"
    />

    <div id="deactivate-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="deactivate-modal-backdrop"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
                <div class="px-6 pt-6 pb-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Nonaktifkan RDM</h3>
                    <p class="text-sm text-slate-500 mt-1" id="deactivate-modal-fqdn"></p>
                </div>

                <form method="POST" id="deactivate-form" class="p-6 space-y-4">
                    @csrf
                    <p class="text-sm text-slate-600">
                        Aplikasi RDM dan akses panel pelanggan akan diblokir. Pelanggan tidak bisa membuka website RDM sampai diaktifkan kembali.
                    </p>
                    <div>
                        <label for="deactivate-reason" class="block text-sm font-medium text-slate-700 mb-1">Alasan (opsional)</label>
                        <textarea id="deactivate-reason" name="reason" rows="3" class="admin-input resize-none" placeholder="Contoh: Tunggakan pembayaran"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="cancel-deactivate-modal" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50">Batal</button>
                        <button type="submit" class="flex-1 admin-btn-danger py-2.5 rounded-xl font-semibold">Nonaktifkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="destroy-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="destroy-modal-backdrop"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-rose-200">
                <div class="px-6 pt-6 pb-4 border-b border-rose-100 bg-rose-50">
                    <h3 class="font-bold text-rose-900">Hapus RDM Permanen</h3>
                    <p class="text-sm text-rose-700 mt-1" id="destroy-modal-fqdn"></p>
                </div>

                <form method="POST" id="destroy-form" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <p class="text-sm text-slate-600">
                        Tindakan ini <strong>tidak dapat dibatalkan</strong>. Yang dihapus:
                    </p>
                    <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                        <li>Folder file RDM</li>
                        <li>Database MySQL</li>
                        <li>Konfigurasi Nginx & SSL</li>
                        <li>Record DNS subdomain</li>
                        <li>Data instance di panel</li>
                    </ul>
                    <p class="text-xs text-slate-500">Riwayat pembayaran tetap disimpan untuk arsip billing.</p>
                    <div>
                        <label for="destroy-confirm" class="block text-sm font-medium text-slate-700 mb-1">
                            Ketik subdomain untuk konfirmasi: <span class="font-mono text-rose-700" id="destroy-subdomain-label"></span>
                        </label>
                        <input type="text" id="destroy-confirm" name="confirm_subdomain" class="admin-input font-mono" autocomplete="off" required>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="cancel-destroy-modal" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50">Batal</button>
                        <button type="submit" class="flex-1 admin-btn-danger py-2.5 rounded-xl font-semibold">Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const deactivateModal = document.getElementById('deactivate-modal');
    const deactivateForm = document.getElementById('deactivate-form');
    const fqdnLabel = document.getElementById('deactivate-modal-fqdn');
    const reasonField = document.getElementById('deactivate-reason');

    const openDeactivate = (fqdn, action) => {
        fqdnLabel.textContent = fqdn;
        deactivateForm.action = action;
        reasonField.value = '';
        deactivateModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeDeactivate = () => {
        deactivateModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-open-deactivate]').forEach((button) => {
        button.addEventListener('click', () => openDeactivate(button.dataset.fqdn, button.dataset.action));
    });

    document.getElementById('cancel-deactivate-modal')?.addEventListener('click', closeDeactivate);
    document.getElementById('deactivate-modal-backdrop')?.addEventListener('click', closeDeactivate);

    const destroyModal = document.getElementById('destroy-modal');
    const destroyForm = document.getElementById('destroy-form');
    const destroyFqdn = document.getElementById('destroy-modal-fqdn');
    const destroySubdomainLabel = document.getElementById('destroy-subdomain-label');
    const destroyConfirm = document.getElementById('destroy-confirm');

    const openDestroy = (fqdn, subdomain, action) => {
        destroyFqdn.textContent = fqdn;
        destroySubdomainLabel.textContent = subdomain;
        destroyForm.action = action;
        destroyConfirm.value = '';
        destroyModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeDestroy = () => {
        destroyModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-open-destroy]').forEach((button) => {
        button.addEventListener('click', () => openDestroy(button.dataset.fqdn, button.dataset.subdomain, button.dataset.action));
    });

    document.getElementById('cancel-destroy-modal')?.addEventListener('click', closeDestroy);
    document.getElementById('destroy-modal-backdrop')?.addEventListener('click', closeDestroy);

    if (typeof window.initDeployRetryButtons === 'function') {
        window.initDeployRetryButtons({
            modalId: 'admin-deploy-progress-modal',
            reloadOnClose: true,
        });
    }

    if (typeof window.initDestroyForm === 'function') {
        window.initDestroyForm({
            reloadOnClose: true,
        });
    }
});
</script>
@endpush
