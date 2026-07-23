@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <x-admin.page-header title="Dashboard Admin" subtitle="Ringkasan sistem RDM-ONLINE.ID" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-admin.stat-card label="Total Pelanggan" :value="$totalCustomers" />
        <x-admin.stat-card label="RDM Aktif" :value="$activeRdm" value-class="text-green-600" />
        <x-admin.stat-card label="Sedang Deploy" :value="$deploying" value-class="text-amber-600" />
        <x-admin.stat-card label="Deploy Gagal" :value="$failed" value-class="text-rose-600" />
    </div>

    <div class="admin-card p-5 mt-6">
        <h3 class="font-semibold text-slate-900 mb-4">Aktivitas Deploy Terbaru</h3>
        <div class="space-y-3 text-sm">
            @forelse ($recentLogs as $log)
                <div class="flex justify-between border-b border-slate-100 pb-2 last:border-0">
                    <span class="text-slate-700">
                        {{ $log->rdmInstance?->fqdn ?? '—' }}: {{ $log->message }}
                    </span>
                    <span class="text-slate-400 shrink-0 ml-4">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-slate-400">Belum ada aktivitas deploy.</p>
            @endforelse
        </div>
    </div>
@endsection
