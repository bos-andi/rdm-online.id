@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-user.page-header title="Dashboard" subtitle="Ringkasan layanan Anda" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-user.stat-card label="RDM Aktif" :value="(string) $stats['activeCount']" />
        <x-user.stat-card
            label="Masa Aktif"
            :value="$stats['daysRemaining'] !== null ? $stats['daysRemaining'].' hari' : '—'"
        />
        <x-user.stat-card label="Penyimpanan" :value="$storageLabel" value-class="text-green-600" />

        <div class="stat-card">
            <p class="text-muted text-sm">Deploy Terakhir</p>
            <p class="text-lg font-medium mt-1 text-gray-800">
                {{ $stats['lastBackupAt']?->translatedFormat('d M Y') ?? '—' }}
            </p>
        </div>

        <div class="stat-card">
            <p class="text-muted text-sm">Status Server</p>
            <p @class([
                'text-lg font-medium mt-1',
                'text-emerald-600' => $stats['serverOnline'],
                'text-gray-400' => ! $stats['serverOnline'],
            ])>
                {{ $stats['serverOnline'] ? '● Online' : '● Offline' }}
            </p>
        </div>

        <x-user.stat-card
            label="Notifikasi"
            :value="(string) $stats['notifications']"
            :value-class="$stats['notifications'] > 0 ? 'text-rose-500' : 'text-gray-600'"
        />
    </div>
@endsection
