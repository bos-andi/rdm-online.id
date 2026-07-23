@extends('layouts.admin')

@section('title', 'DNS & Cloudflare')

@section('content')
    <x-admin.page-header title="DNS & Hostinger" subtitle="Zone {{ $domain }}" />

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">API Status</p>
            <p @class([
                'text-lg font-semibold mt-1',
                'text-emerald-600' => $apiOnline,
                'text-rose-600' => ! $apiOnline,
            ])>
                {{ $apiOnline ? '● Terhubung' : '● Tidak terhubung' }}
            </p>
        </div>
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">Total Record</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalRecords }}</p>
        </div>
        <div class="admin-stat-card">
            <p class="text-slate-500 text-sm">Server IP</p>
            <p class="text-lg font-semibold text-slate-900 mt-1">{{ $serverIp }}</p>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">
            Record Subdomain RDM
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-t border-slate-100">
                    <th class="p-3 text-left font-semibold text-slate-700">Nama</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Tipe</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Konten</th>
                    <th class="p-3 text-left font-semibold text-slate-700">TTL</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subdomainRecords as $record)
                    <tr class="border-t border-slate-100">
                        <td class="p-3 font-medium text-emerald-700">{{ $record['name'] }}.{{ $domain }}</td>
                        <td class="p-3">{{ $record['type'] }}</td>
                        <td class="p-3 text-slate-500">
                            {{ collect($record['records'] ?? [])->pluck('content')->implode(', ') }}
                        </td>
                        <td class="p-3 text-slate-500">{{ $record['ttl'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-slate-400">
                            @if (! $apiConfigured)
                                Token Hostinger belum dikonfigurasi di .env
                            @else
                                Belum ada record subdomain RDM.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
