@extends('layouts.affiliate')

@section('title', 'Referral Saya')

@php($rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.'))
@php($maskEmail = fn (string $email) => preg_replace('/(?<=^.{2})[^@]*(?=@)/', '***', $email))

@section('content')
    <x-user.page-header title="Referral Saya" subtitle="Madrasah yang mendaftar melalui link atau kode referral Anda." />

    <div class="app-card overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="app-card-header">
                    <th class="p-3 text-left font-semibold text-gray-700">Madrasah</th>
                    <th class="p-3 text-left font-semibold text-gray-700">Bergabung</th>
                    <th class="p-3 text-left font-semibold text-gray-700">RDM</th>
                    <th class="p-3 text-right font-semibold text-gray-700">Komisi dari madrasah ini</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($referrals as $user)
                    <tr class="border-t border-emerald-50">
                        <td class="p-3">
                            <p class="font-medium text-gray-800">{{ $user->madrasah_name ?: $user->name }}</p>
                            <p class="text-xs text-muted">{{ $maskEmail($user->email) }}</p>
                        </td>
                        <td class="p-3 text-muted">{{ $user->referred_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="p-3">
                            @forelse ($user->rdmInstances as $rdm)
                                @php($active = $rdm->status === 'active')
                                <span class="inline-flex items-center gap-1.5 text-xs mr-2">
                                    <span @class(['w-2 h-2 rounded-full', 'bg-emerald-500' => $active, 'bg-amber-400' => $rdm->status === 'pending_payment', 'bg-gray-300' => ! $active && $rdm->status !== 'pending_payment'])></span>
                                    {{ $rdm->subdomain }}
                                    <span class="text-muted">({{ $active ? 'Aktif' : ($rdm->status === 'pending_payment' ? 'Belum bayar' : ucfirst($rdm->status)) }})</span>
                                </span>
                            @empty
                                <span class="text-xs text-muted">Belum membuat RDM</span>
                            @endforelse
                        </td>
                        <td class="p-3 text-right font-semibold text-emerald-700">{{ $rupiah((int) $user->commission_total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-10 text-center text-muted">
                            Belum ada madrasah yang mendaftar lewat referral Anda.
                            <a href="{{ route('affiliate.dashboard') }}" class="text-emerald-700 hover:underline">Salin link referral</a> dan mulai bagikan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $referrals->links() }}</div>
@endsection
