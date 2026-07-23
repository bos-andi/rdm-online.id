@props([
    'id' => 'deploy-progress-modal',
    'title' => 'Deploying RDM...',
    'subtitle' => 'Mohon tunggu, proses berjalan di server.',
    'doneMessage' => 'Deploy berhasil! Notifikasi email telah dikirim.',
    'dashboardUrl' => null,
    'dashboardLabel' => 'Tutup',
    'showOpenLink' => true,
    'stepLabels' => [],
    'hidden' => true,
])

<div
    id="{{ $id }}"
    @class(['fixed inset-0 z-50', 'hidden' => $hidden])
    data-deploy-progress-modal
    @if ($stepLabels)
        data-progress-step-labels='@json(collect($stepLabels)->pluck("label")->values())'
    @endif
>
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-progress-backdrop></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200">
            <div class="px-6 pt-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900" data-progress-title>{{ $title }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5" data-progress-fqdn></p>
                </div>
                <button type="button" data-progress-close class="p-1 rounded-lg hover:bg-slate-100 text-slate-400">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="relative w-20 h-20 mx-auto mb-3">
                        <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="34" fill="none" stroke="#e2e8f0" stroke-width="6"/>
                            <circle data-progress-ring cx="40" cy="40" r="34" fill="none" stroke="#059669" stroke-width="6"
                                stroke-dasharray="213.6" stroke-dashoffset="213.6" stroke-linecap="round" class="transition-all duration-500"/>
                        </svg>
                        <span data-progress-pct class="absolute inset-0 flex items-center justify-center text-lg font-bold text-emerald-700">0%</span>
                    </div>
                    <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                    <p data-progress-label class="text-sm font-medium text-slate-800 mt-2">Memulai...</p>
                </div>

                <div data-progress-steps class="space-y-2 text-sm max-h-48 overflow-y-auto"></div>

                <div data-progress-done class="hidden mt-6 text-center space-y-3">
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm" data-progress-done-message>
                        {{ $doneMessage }}
                    </div>
                    @if ($showOpenLink)
                        <a data-progress-url href="#" target="_blank" rel="noopener" class="inline-block px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">
                            Buka RDM
                        </a>
                    @endif
                    @if ($dashboardUrl)
                        <a href="{{ $dashboardUrl }}" data-progress-dashboard class="block text-sm text-emerald-700 font-medium hover:underline">
                            {{ $dashboardLabel }}
                        </a>
                    @endif
                </div>

                <div data-progress-error class="hidden mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm"></div>
            </div>
        </div>
    </div>
</div>
