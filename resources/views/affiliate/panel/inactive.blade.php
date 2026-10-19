@extends('layouts.affiliate')

@section('title', 'Status Akun')

@section('content')
    <x-user.page-header title="Halo, {{ $affiliate->name }}" subtitle="Status akun affiliate Anda" />

    <div class="app-card p-8 max-w-2xl">
        @if ($affiliate->status === 'pending')
            <div class="flex items-start gap-4">
                <span class="flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 shrink-0">
                    <i data-lucide="hourglass" class="w-6 h-6"></i>
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Pendaftaran sedang ditinjau</h3>
                    <p class="text-muted mt-2">
                        Terima kasih sudah mendaftar! Tim kami sedang meninjau data Anda — biasanya selesai dalam 1×24 jam kerja.
                        Kami akan mengirim email ke <strong>{{ $affiliate->email }}</strong> begitu akun disetujui.
                    </p>
                    <p class="text-muted mt-3">Sambil menunggu, lengkapi <a href="{{ route('affiliate.profile') }}" class="text-emerald-700 font-medium hover:underline">data rekening</a> agar pencairan komisi lancar nanti.</p>
                </div>
            </div>
        @elseif ($affiliate->status === 'rejected')
            <div class="flex items-start gap-4">
                <span class="flex items-center justify-center w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 shrink-0">
                    <i data-lucide="circle-x" class="w-6 h-6"></i>
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Pendaftaran belum disetujui</h3>
                    <p class="text-muted mt-2">Mohon maaf, pendaftaran affiliate Anda belum dapat kami setujui. Hubungi <a href="mailto:{{ config('company.email') }}" class="text-emerald-700 hover:underline">{{ config('company.email') }}</a> untuk informasi lebih lanjut.</p>
                </div>
            </div>
        @else
            <div class="flex items-start gap-4">
                <span class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gray-100 text-gray-600 shrink-0">
                    <i data-lucide="pause-circle" class="w-6 h-6"></i>
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Akun sedang dinonaktifkan</h3>
                    <p class="text-muted mt-2">Akun affiliate Anda sementara dinonaktifkan. Hubungi <a href="mailto:{{ config('company.email') }}" class="text-emerald-700 hover:underline">{{ config('company.email') }}</a> untuk mengaktifkan kembali.</p>
                </div>
            </div>
        @endif
    </div>
@endsection
