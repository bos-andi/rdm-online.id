@extends('layouts.admin')

@section('title', 'Pengumuman')

@section('content')
    <x-admin.page-header title="Pengumuman" subtitle="Broadcast informasi ke seluruh pelanggan" />

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-2 admin-card p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Buat Pengumuman</h2>
            <form method="POST" action="{{ route('admin.pengumuman.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Judul</label>
                    <input name="title" value="{{ old('title') }}" required class="admin-input" placeholder="Maintenance server...">
                    @error('title')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Isi</label>
                    <textarea name="body" rows="5" required class="admin-input" placeholder="Detail pengumuman...">{{ old('body') }}</textarea>
                    @error('body')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="admin-btn w-full py-2.5 rounded-lg font-semibold">Publikasikan</button>
            </form>
        </div>

        <div class="lg:col-span-3 space-y-4">
            @forelse ($announcements as $item)
                <div class="admin-card p-5">
                    <div class="flex justify-between items-start gap-4">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $item->title }}</h3>
                            <p class="text-slate-600 text-sm mt-2 whitespace-pre-line">{{ $item->body }}</p>
                            <p class="text-xs text-slate-400 mt-3">{{ $item->created_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.pengumuman.destroy', $item) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="admin-card p-8 text-center text-slate-400">
                    Belum ada pengumuman.
                </div>
            @endforelse
        </div>
    </div>
@endsection
