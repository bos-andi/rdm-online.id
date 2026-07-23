@extends('layouts.admin')

@section('title', 'Database Manager')

@section('content')
    <x-admin.page-header title="Database Manager" subtitle="{{ $total }} database RDM terdaftar" />

    <div class="admin-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="admin-card-header">
                <tr>
                    <th class="p-3 text-left font-semibold text-slate-700">Database</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Domain</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Ukuran</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($instances as $rdm)
                    @php
                        $dbInfo = $databases->firstWhere('name', $rdm->db_name);
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="p-3 font-mono text-slate-800">{{ $rdm->db_name }}</td>
                        <td class="p-3">{{ $rdm->madrasah_name }}</td>
                        <td class="p-3 text-emerald-700">{{ $rdm->fqdn }}</td>
                        <td class="p-3 text-slate-500">{{ $dbInfo?->size_mb ? $dbInfo->size_mb.' MB' : '—' }}</td>
                        <td class="p-3">
                            <x-admin.badge
                                :status="ucfirst($rdm->status)"
                                :variant="match($rdm->status) { 'active' => 'success', 'failed' => 'danger', 'deploying' => 'warning', default => 'default' }"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-400">Belum ada database RDM.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
