@extends('layouts.app')

@section('title', 'Monitoring')

@section('content')
    <x-user.page-header title="Monitoring" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
        <x-user.stat-card label="RDM Online" :value="(string) $activeCount" />
        <div class="stat-card">
            <p class="text-muted text-sm">Status Platform</p>
            <p class="text-lg font-medium text-emerald-600 mt-1">● Online</p>
        </div>
        <div class="stat-card">
            <p class="text-muted text-sm">Server</p>
            <p class="text-lg font-medium text-emerald-600 mt-1">{{ config('deploy.server_ip') }}</p>
        </div>
    </div>

    @if ($instances->isEmpty())
        <div class="app-card p-8 text-center text-muted">
            Belum ada RDM aktif untuk dimonitor.
        </div>
    @else
        <div class="app-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="app-card-header">
                    <tr>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Madrasah</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Domain</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Status</th>
                        <th class="p-3 text-left text-emerald-800 font-semibold">Deploy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($instances as $rdm)
                        <tr class="border-t app-divider">
                            <td class="p-3 font-medium">{{ $rdm->madrasah_name }}</td>
                            <td class="p-3 text-emerald-600">{{ $rdm->fqdn }}</td>
                            <td class="p-3"><x-user.badge status="Online" /></td>
                            <td class="p-3 text-muted">{{ $rdm->deployed_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
