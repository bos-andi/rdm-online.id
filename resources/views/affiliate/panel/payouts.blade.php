@extends('layouts.affiliate')

@section('title', 'Pencairan')

@php
    $rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $canRequest = ! $hasOpenRequest && $affiliate->isBankVerified() && $balance['available'] > 0 && $balance['available'] >= $settings['min_payout'];
@endphp

@section('content')
    <x-user.page-header title="Pencairan Komisi" subtitle="Ajukan pencairan, admin akan mentransfer ke rekening Anda." />

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <div class="app-card p-6 lg:col-span-2">
            <p class="text-sm text-muted">Saldo siap dicairkan</p>
            <p class="text-4xl font-bold text-emerald-700 mt-1">{{ $rupiah($balance['available']) }}</p>
            <p class="text-xs text-muted mt-2">Minimal pencairan {{ $rupiah($settings['min_payout']) }} · Seluruh saldo yang tersedia diajukan sekaligus.</p>

            <form method="POST" action="{{ route('affiliate.payouts.request') }}" class="mt-5">
                @csrf
                <button type="submit" @disabled(! $canRequest) class="app-btn px-6 py-3 rounded-xl font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                    Ajukan Pencairan {{ $balance['available'] > 0 ? $rupiah($balance['available']) : '' }}
                </button>
            </form>

            @if ($hasOpenRequest)
                <p class="mt-3 text-sm text-amber-700">Pengajuan sebelumnya masih diproses admin.</p>
            @elseif (! $affiliate->hasBankAccount() || $affiliate->bank_verification_status === 'unsubmitted')
                <p class="mt-3 text-sm text-amber-700">Lengkapi <a href="{{ route('affiliate.profile') }}" class="font-medium underline">data rekening, foto KTP, dan buku tabungan</a> untuk verifikasi sebelum mengajukan pencairan.</p>
            @elseif ($affiliate->bank_verification_status === 'pending')
                <p class="mt-3 text-sm text-amber-700">Rekening Anda sedang diverifikasi admin. Pencairan bisa diajukan setelah terverifikasi.</p>
            @elseif ($affiliate->bank_verification_status === 'rejected')
                <p class="mt-3 text-sm text-rose-700">Verifikasi rekening ditolak: {{ $affiliate->bank_verification_note }} — <a href="{{ route('affiliate.profile') }}" class="font-medium underline">perbaiki di sini</a>.</p>
            @elseif ($balance['available'] < $settings['min_payout'])
                <p class="mt-3 text-sm text-muted">Saldo belum mencapai batas minimal pencairan.</p>
            @endif
        </div>

        <div class="app-card p-6">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-semibold text-gray-900">Rekening Tujuan</h3>
                <x-user.badge :status="$affiliate->bankStatusLabel()" :variant="$affiliate->bankStatusVariant()" />
            </div>
            @if ($affiliate->hasBankAccount())
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-muted text-xs">Bank / E-wallet</dt><dd class="font-medium">{{ $affiliate->bank_name }}</dd></div>
                    <div><dt class="text-muted text-xs">Nomor</dt><dd class="font-medium">{{ $affiliate->bank_account_number }}</dd></div>
                    <div><dt class="text-muted text-xs">Atas nama</dt><dd class="font-medium">{{ $affiliate->bank_account_name }}</dd></div>
                </dl>
            @else
                <p class="mt-3 text-sm text-muted">Belum diisi.</p>
            @endif
            <a href="{{ route('affiliate.profile') }}" class="mt-4 inline-block text-sm text-emerald-700 hover:underline">Ubah rekening</a>
        </div>
    </div>

    <div class="app-card overflow-x-auto">
        <div class="app-card-header px-5 py-3 font-semibold text-gray-800">Riwayat Pencairan</div>
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="border-t border-emerald-50">
                    <th class="p-3 text-left font-semibold text-gray-700">Diajukan</th>
                    <th class="p-3 text-right font-semibold text-gray-700">Jumlah</th>
                    <th class="p-3 text-left font-semibold text-gray-700">Rekening</th>
                    <th class="p-3 text-left font-semibold text-gray-700">Status</th>
                    <th class="p-3 text-left font-semibold text-gray-700">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payouts as $payout)
                    <tr class="border-t border-emerald-50">
                        <td class="p-3 text-muted whitespace-nowrap">{{ $payout->created_at->translatedFormat('d M Y') }}</td>
                        <td class="p-3 text-right font-semibold whitespace-nowrap">{{ $rupiah($payout->amount) }}</td>
                        <td class="p-3 text-xs text-muted">{{ $payout->bank_name }} {{ $payout->bank_account_number }}<br>a.n. {{ $payout->bank_account_name }}</td>
                        <td class="p-3"><x-user.badge :status="$payout->statusLabel()" :variant="$payout->statusVariant()" /></td>
                        <td class="p-3 text-xs text-muted">
                            @if ($payout->processed_at)
                                {{ $payout->processed_at->translatedFormat('d M Y') }}
                            @endif
                            @if ($payout->transfer_reference)
                                <br>Ref: {{ $payout->transfer_reference }}
                            @endif
                            @if ($payout->admin_note)
                                <br>{{ $payout->admin_note }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-muted">Belum ada pengajuan pencairan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payouts->links() }}</div>
@endsection
