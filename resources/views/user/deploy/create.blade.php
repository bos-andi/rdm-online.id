@extends('layouts.app')

@section('title', 'Buat RDM Baru')

@section('content')
    <x-user.page-header title="Buat RDM Baru" subtitle="One Click Deploy" />

    <div class="app-card p-6 max-w-lg">
        <p class="text-muted text-sm mb-6">
            Deploy RDM madrasah baru dalam beberapa langkah.
        </p>
        <button type="button" id="open-deploy-wizard" class="w-full app-btn font-semibold py-3 rounded-lg inline-flex items-center justify-center gap-2">
            <i data-lucide="rocket" class="w-5 h-5"></i>
            Mulai Deploy RDM
        </button>
    </div>

    @include('deploy.partials.wizard', ['user' => auth()->user(), 'isGuest' => false])
@endsection
