@extends('layouts.affiliate')

@section('title', 'Profil & Rekening')

@php
    $bankStatus = $affiliate->bank_verification_status;
    $statusBox = match ($bankStatus) {
        'verified' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'pending' => 'bg-amber-50 border-amber-200 text-amber-800',
        'rejected' => 'bg-rose-50 border-rose-200 text-rose-800',
        default => 'bg-slate-50 border-slate-200 text-slate-700',
    };
    $documentHints = [
        'ktp' => 'KTP atas nama pemilik rekening. Pastikan nama dan NIK terbaca jelas.',
        'bank_book' => 'Halaman buku tabungan yang menampilkan nama & nomor rekening. Untuk e-wallet, screenshot halaman profil akun.',
    ];
@endphp

@section('content')
    <x-user.page-header title="Profil & Rekening" subtitle="Data kontak dan rekening untuk pencairan komisi." />

    <div class="grid gap-6 lg:grid-cols-5">
        <form method="POST" action="{{ route('affiliate.profile.bank') }}" enctype="multipart/form-data" class="app-card p-6 space-y-4 lg:col-span-3">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold text-gray-900">Rekening Pencairan</h3>
                <x-user.badge :status="$affiliate->bankStatusLabel()" :variant="$affiliate->bankStatusVariant()" />
            </div>

            <div class="p-3 rounded-xl border text-sm {{ $statusBox }}">
                @switch($bankStatus)
                    @case('verified')
                        Rekening terverifikasi{{ $affiliate->bank_verified_at ? ' sejak '.$affiliate->bank_verified_at->translatedFormat('d M Y') : '' }}. Mengubah data rekening akan memerlukan verifikasi ulang.
                        @break
                    @case('pending')
                        Dokumen sedang diperiksa admin. Pencairan bisa diajukan setelah rekening terverifikasi.
                        @break
                    @case('rejected')
                        <strong>Verifikasi ditolak:</strong> {{ $affiliate->bank_verification_note }}<br>
                        Perbaiki data atau upload ulang dokumen, lalu kirim kembali.
                        @break
                    @default
                        Untuk keamanan, pencairan hanya dikirim ke rekening yang sudah diverifikasi. Lengkapi data rekening, foto KTP, dan foto buku tabungan di bawah.
                @endswitch
            </div>

            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Bank / E-wallet</label>
                <input id="bank_name" name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" required class="app-input" placeholder="BCA, BRI, BSI, DANA, …">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor rekening / akun</label>
                    <input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $affiliate->bank_account_number) }}" required class="app-input" inputmode="numeric">
                </div>
                <div>
                    <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">Atas nama</label>
                    <input id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $affiliate->bank_account_name) }}" required class="app-input" placeholder="Sesuai buku tabungan">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (\App\Models\Affiliate::DOCUMENTS as $type => $label)
                    @php($hasFile = (bool) $affiliate->documentPath($type))
                    <div class="rounded-xl border border-dashed border-emerald-200 p-4">
                        <label for="doc-{{ $type }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                        <p class="text-xs text-muted mt-1 mb-3">{{ $documentHints[$type] }}</p>

                        @if ($hasFile)
                            <a href="{{ route('affiliate.document', $type) }}" target="_blank" class="block mb-3 rounded-lg overflow-hidden border border-emerald-100 bg-emerald-50/50">
                                @if ($affiliate->documentIsPdf($type))
                                    <span class="flex items-center gap-2 p-3 text-sm text-emerald-700"><i data-lucide="file-text" class="w-4 h-4"></i> Lihat file PDF</span>
                                @else
                                    <img src="{{ route('affiliate.document', $type) }}" alt="{{ $label }}" class="w-full h-32 object-cover">
                                @endif
                            </a>
                        @endif

                        <input id="doc-{{ $type }}" name="{{ $type }}" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" @if (! $hasFile && $type === 'ktp') required @endif
                            class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:text-emerald-800 file:font-medium hover:file:bg-emerald-200">
                        <p class="text-xs text-muted mt-2">{{ $hasFile ? 'Pilih file baru hanya jika ingin mengganti.' : 'JPG, PNG, WEBP, atau PDF · maks. 8 MB' }}</p>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-muted">Nama di KTP dan buku tabungan harus sama dengan nama pemilik rekening. Dokumen hanya bisa dilihat oleh Anda dan admin RDM Online.</p>

            <button type="submit" class="app-btn w-full py-2.5 rounded-xl font-semibold">
                {{ $bankStatus === 'unsubmitted' ? 'Kirim untuk Verifikasi' : 'Simpan & Kirim Ulang Verifikasi' }}
            </button>
        </form>

        <div class="space-y-6 lg:col-span-2">
            <form method="POST" action="{{ route('affiliate.profile.update') }}" class="app-card p-6 space-y-4">
                @csrf
                <h3 class="font-semibold text-gray-900">Data Diri</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" value="{{ $affiliate->email }}" class="app-input bg-emerald-50" readonly>
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama lengkap</label>
                    <input id="name" name="name" value="{{ old('name', $affiliate->name) }}" required class="app-input">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                    <input id="phone" name="phone" value="{{ old('phone', $affiliate->phone) }}" required class="app-input" placeholder="08xxxxxxxxxx">
                </div>
                <button type="submit" class="app-btn w-full py-2.5 rounded-xl font-semibold">Simpan</button>
            </form>

            <div class="app-card p-6">
                <h3 class="font-semibold text-gray-900">Kode Referral</h3>
                <p class="mt-2 text-2xl font-bold tracking-wide text-emerald-700">{{ $affiliate->code }}</p>
                <p class="text-xs text-muted mt-1">Hubungi admin jika ingin mengganti kode.</p>
            </div>

            <form method="POST" action="{{ route('affiliate.profile.password') }}" class="app-card p-6 space-y-4">
                @csrf
                <h3 class="font-semibold text-gray-900">Ubah Password</h3>
                <x-password-input id="current_password" name="current_password" label="Password saat ini" />
                <x-password-input id="password" name="password" label="Password baru" placeholder="Min. 8 karakter" />
                <x-password-input id="password_confirmation" name="password_confirmation" label="Ulangi password baru" />
                <button type="submit" class="app-btn w-full py-2.5 rounded-xl font-semibold">Ubah Password</button>
            </form>
        </div>
    </div>
@endsection
