@extends('layouts.admin')

@section('title', 'Pengaturan Sistem')

@section('content')
    <x-admin.page-header title="Pengaturan Sistem" subtitle="Status konfigurasi layanan & harga paket" />

    @if (session('status'))
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="admin-card p-6 mb-6">
        <h2 class="font-semibold text-slate-800 mb-1">Harga Paket RDM</h2>
        <p class="text-xs text-slate-400 mb-5">Harga jual dipakai di deploy & checkout. Harga coretan + batas waktu menampilkan promo di landing & wizard.</p>

        <form method="POST" action="{{ route('admin.settings.pricing') }}" class="grid gap-4 sm:grid-cols-2 max-w-2xl">
            @csrf
            <div>
                <label for="monthly_price" class="block text-sm font-medium text-slate-700 mb-1">Harga Bulanan (Rp)</label>
                <input
                    id="monthly_price"
                    name="monthly_price"
                    type="number"
                    min="0"
                    step="1000"
                    required
                    value="{{ old('monthly_price', $packagePrices['monthly']) }}"
                    class="admin-input"
                >
                @error('monthly_price')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="monthly_original" class="block text-sm font-medium text-slate-700 mb-1">Harga Coretan Bulanan (Rp)</label>
                <input
                    id="monthly_original"
                    name="monthly_original"
                    type="number"
                    min="0"
                    step="1000"
                    value="{{ old('monthly_original', $monthlyOriginal) }}"
                    class="admin-input"
                    placeholder="Kosongkan jika tanpa diskon"
                >
                @error('monthly_original')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="yearly_price" class="block text-sm font-medium text-slate-700 mb-1">Harga Tahunan (Rp)</label>
                <input
                    id="yearly_price"
                    name="yearly_price"
                    type="number"
                    min="0"
                    step="1000"
                    required
                    value="{{ old('yearly_price', $packagePrices['yearly']) }}"
                    class="admin-input"
                >
                @error('yearly_price')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="yearly_original" class="block text-sm font-medium text-slate-700 mb-1">Harga Coretan Tahunan (Rp)</label>
                <input
                    id="yearly_original"
                    name="yearly_original"
                    type="number"
                    min="0"
                    step="1000"
                    value="{{ old('yearly_original', $yearlyOriginal ?? 100000) }}"
                    class="admin-input"
                    placeholder="Contoh: 100000"
                >
                @error('yearly_original')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="biennial_price" class="block text-sm font-medium text-slate-700 mb-1">Harga 2 Tahunan (Rp)</label>
                <input
                    id="biennial_price"
                    name="biennial_price"
                    type="number"
                    min="0"
                    step="1000"
                    required
                    value="{{ old('biennial_price', $packagePrices['biennial']) }}"
                    class="admin-input"
                >
                @error('biennial_price')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="biennial_original" class="block text-sm font-medium text-slate-700 mb-1">Harga Coretan 2 Tahunan (Rp)</label>
                <input
                    id="biennial_original"
                    name="biennial_original"
                    type="number"
                    min="0"
                    step="1000"
                    value="{{ old('biennial_original', $biennialOriginal) }}"
                    class="admin-input"
                    placeholder="Kosongkan jika tanpa diskon"
                >
                @error('biennial_original')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="promo_ends_at" class="block text-sm font-medium text-slate-700 mb-1">Batas Waktu Promo</label>
                <input
                    id="promo_ends_at"
                    name="promo_ends_at"
                    type="datetime-local"
                    value="{{ old('promo_ends_at', $promoEndsAt?->format('Y-m-d\TH:i')) }}"
                    class="admin-input max-w-md"
                >
                <p class="text-xs text-slate-400 mt-1">Countdown tampil selama ada harga coretan dan waktu belum habis. Kosongkan untuk nonaktifkan countdown (coretan tetap tampil jika harga coretan &gt; harga jual).</p>
                @error('promo_ends_at')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="admin-btn px-5 py-2.5 rounded-lg font-semibold">
                    Simpan Harga
                </button>
            </div>
        </form>
    </div>

    <div class="space-y-3">
        @foreach ($settings as $setting)
            <div class="admin-card p-4 flex justify-between items-center gap-4">
                <div>
                    <span class="font-medium text-slate-800">{{ $setting['label'] }}</span>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $setting['detail'] }}</p>
                </div>
                <span @class([
                    'text-xs font-medium',
                    'text-emerald-600' => $setting['configured'],
                    'text-rose-600' => ! $setting['configured'],
                ])>
                    {{ $setting['configured'] ? '● Configured' : '● Not configured' }}
                </span>
            </div>
        @endforeach
    </div>
@endsection
