@extends('layouts.app')

@section('title', 'Support Center')

@section('content')
    <x-user.page-header title="Support Center" />

    @if ($announcements->isNotEmpty())
        <div class="mb-6 space-y-3">
            <h2 class="text-sm font-semibold text-emerald-900">Pengumuman</h2>
            @foreach ($announcements as $item)
                <div class="app-card p-4">
                    <h3 class="font-semibold text-gray-900">{{ $item->title }}</h3>
                    <p class="text-sm text-muted mt-2 whitespace-pre-line">{{ $item->body }}</p>
                    <p class="text-xs text-gray-400 mt-2">{{ $item->created_at->translatedFormat('d M Y') }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="space-y-3">
        <div class="app-card p-4">
            <p class="text-muted text-sm">
                Butuh bantuan teknis? Hubungi admin melalui email
                <a href="mailto:{{ config('mail.from.address') }}" class="text-emerald-700 font-medium">{{ config('mail.from.address') }}</a>.
            </p>
        </div>
        <a
            href="https://wa.me/{{ config('company.whatsapp') }}"
            target="_blank"
            rel="noopener noreferrer"
            class="block w-full app-btn rounded-xl p-4 text-center font-semibold"
        >
            Live Chat WhatsApp
        </a>
    </div>
@endsection
