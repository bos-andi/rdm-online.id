@extends('layouts.admin')

@section('title', 'Pencairan Affiliate')

@php($rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.'))

@section('content')
    <a href="{{ route('admin.affiliate') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Affiliate</a>
    <x-admin.page-header title="Pencairan Komisi" subtitle="Transfer manual ke rekening affiliate, lalu tandai sudah ditransfer." class="mt-2" />

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">{{ $errors->first() }}</div>
    @endif

    <div class="admin-card overflow-x-auto">
        <div class="admin-card-header px-4 py-3 flex flex-wrap gap-2 text-sm">
            @foreach (['requested' => 'Perlu diproses', 'paid' => 'Sudah ditransfer', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
                <a href="{{ route('admin.affiliate.payouts', ['status' => $key]) }}" @class([
                    'px-3 py-1 rounded-full',
                    'bg-slate-900 text-white' => $status === $key,
                    'text-slate-600 hover:bg-slate-100' => $status !== $key,
                ])>{{ $label }}</a>
            @endforeach
        </div>
        <table class="w-full text-sm min-w-[860px]">
            <thead>
                <tr class="border-t border-slate-100">
                    <th class="p-3 text-left font-semibold text-slate-700">Diajukan</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Affiliate</th>
                    <th class="p-3 text-right font-semibold text-slate-700">Jumlah</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Rekening tujuan</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Status / Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payouts as $payout)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="p-3 text-slate-500 whitespace-nowrap">{{ $payout->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="p-3">
                            <a href="{{ route('admin.affiliate.show', $payout->affiliate) }}" class="font-medium hover:text-emerald-700">{{ $payout->affiliate->name }}</a>
                            <p class="text-xs text-slate-500">{{ $payout->affiliate->code }} · {{ $payout->affiliate->phone }}</p>
                        </td>
                        <td class="p-3 text-right font-bold whitespace-nowrap">{{ $rupiah($payout->amount) }}</td>
                        <td class="p-3">
                            <p>{{ $payout->bank_name }}</p>
                            <p class="font-mono">{{ $payout->bank_account_number }}</p>
                            <p class="text-xs text-slate-500">a.n. {{ $payout->bank_account_name }}</p>
                        </td>
                        <td class="p-3">
                            @if ($payout->status === 'requested')
                                <form method="POST" action="{{ route('admin.affiliate.payouts.pay', $payout) }}" class="space-y-2">
                                    @csrf
                                    <input name="transfer_reference" class="admin-input text-xs" placeholder="No. referensi transfer (opsional)">
                                    <div class="flex gap-2">
                                        <button class="admin-btn px-3 py-1.5 rounded-lg text-xs font-semibold" onclick="return confirm('Tandai {{ $rupiah($payout->amount) }} sudah ditransfer ke {{ $payout->affiliate->name }}?')">Sudah Ditransfer</button>
                                        <button type="submit" form="reject-{{ $payout->id }}" class="px-3 py-1.5 rounded-lg text-xs border border-rose-200 text-rose-700 hover:bg-rose-50">Tolak</button>
                                    </div>
                                </form>
                                <form id="reject-{{ $payout->id }}" method="POST" action="{{ route('admin.affiliate.payouts.reject', $payout) }}" onsubmit="const r = prompt('Alasan penolakan (dikirim ke affiliate):'); if (r === null) return false; this.admin_note.value = r;">
                                    @csrf
                                    <input type="hidden" name="admin_note" value="">
                                </form>
                            @else
                                <x-admin.badge :status="$payout->statusLabel()" :variant="$payout->statusVariant()" />
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $payout->processed_at?->translatedFormat('d M Y') }}
                                    @if ($payout->transfer_reference) · Ref {{ $payout->transfer_reference }} @endif
                                </p>
                                @if ($payout->admin_note)<p class="text-xs text-slate-500">{{ $payout->admin_note }}</p>@endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">Tidak ada data pencairan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $payouts->links() }}</div>
    </div>
@endsection
