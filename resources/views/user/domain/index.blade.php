@extends('layouts.app')

@section('title', 'Domain & DNS')

@section('content')
    <x-user.page-header title="Domain & DNS" />

    @if ($instances->isEmpty())
        <div class="app-card p-8 text-center text-muted">
            Belum ada domain RDM aktif.
        </div>
    @else
        <div class="app-card p-5 space-y-3">
            @foreach ($instances as $rdm)
                <div @class([
                    'flex justify-between items-center py-2',
                    'border-b app-divider' => ! $loop->last,
                ])>
                    <span class="text-emerald-800">{{ $rdm->fqdn }}</span>
                    <x-user.badge status="SSL Active" />
                </div>
            @endforeach
        </div>
    @endif
@endsection
