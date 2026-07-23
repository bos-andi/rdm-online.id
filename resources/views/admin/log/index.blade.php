@extends('layouts.admin')

@section('title', 'Log Aktivitas')

@section('content')
    <x-admin.page-header title="Log Aktivitas" subtitle="Log deploy dari database" />

    <div class="admin-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="admin-card-header">
                <tr>
                    <th class="p-3 text-left font-semibold text-slate-700">Waktu</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Instance</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Step</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Pesan</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t border-slate-100">
                        <td class="p-3 text-slate-400 whitespace-nowrap">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="p-3 text-emerald-700">{{ $log->rdmInstance?->fqdn ?? '—' }}</td>
                        <td class="p-3 font-mono text-xs">{{ $log->step }}</td>
                        <td class="p-3 text-slate-700">{{ $log->message }}</td>
                        <td class="p-3">
                            <x-admin.badge
                                :status="ucfirst($log->level)"
                                :variant="match($log->level) { 'error' => 'danger', 'warning' => 'warning', default => 'default' }"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-400">Belum ada log aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
