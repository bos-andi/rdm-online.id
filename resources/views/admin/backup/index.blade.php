@extends('layouts.admin')

@section('title', 'Backup Center')

@section('content')
    <x-admin.page-header title="Backup Center" subtitle="Referensi database per instance RDM aktif" />

    @if ($instances->isEmpty())
        <div class="admin-card p-8 text-center text-slate-400">
            Belum ada RDM aktif untuk di-backup.
        </div>
    @else
        <div class="admin-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="admin-card-header">
                    <tr>
                        <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Domain</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Database</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Deploy Terakhir</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Path</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($instances as $rdm)
                        <tr class="border-t border-slate-100">
                            <td class="p-3 font-medium">{{ $rdm->madrasah_name }}</td>
                            <td class="p-3 text-emerald-700">{{ $rdm->fqdn }}</td>
                            <td class="p-3 font-mono text-slate-600">{{ $rdm->db_name }}</td>
                            <td class="p-3 text-slate-500">
                                {{ $rdm->deployed_at?->translatedFormat('d M Y H:i') ?? '—' }}
                            </td>
                            <td class="p-3 text-slate-400 text-xs truncate max-w-[200px]">{{ $rdm->deploy_path }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
