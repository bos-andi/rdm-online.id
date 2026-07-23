@extends('layouts.app')

@section('title', 'Backup & Restore')

@section('content')
    <x-user.page-header title="Backup & Restore" />

    @if ($instances->isEmpty())
        <div class="app-card p-8 text-center text-muted">
            Belum ada RDM untuk di-backup.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($instances as $rdm)
                <div class="app-card p-5">
                    <h3 class="font-semibold mb-1 text-emerald-900">{{ $rdm->madrasah_name }}</h3>
                    <p class="text-muted text-sm mb-3">{{ $rdm->fqdn }}</p>
                    <p class="text-sm text-muted">Database</p>
                    <p class="font-medium text-gray-800">{{ $rdm->db_name }}</p>
                    <p class="text-sm text-muted mt-3">Deploy terakhir</p>
                    <p class="text-emerald-700 text-sm font-medium">
                        {{ $rdm->deployed_at?->translatedFormat('d M Y H:i') ?? '—' }}
                    </p>
                </div>
            @endforeach
        </div>
    @endif
@endsection
