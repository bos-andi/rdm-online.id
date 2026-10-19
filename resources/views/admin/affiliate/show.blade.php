@extends('layouts.admin')

@section('title', 'Affiliate — '.$affiliate->name)

@php
    $rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $service = app(\App\Services\AffiliateService::class);
@endphp

@section('content')
    <a href="{{ route('admin.affiliate') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Semua affiliate</a>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                {{ $affiliate->name }}
                <x-admin.badge :status="$affiliate->statusLabel()" :variant="$affiliate->statusVariant()" />
                @unless ($affiliate->hasVerifiedEmail())
                    <x-admin.badge status="Email belum diverifikasi" variant="danger" />
                @endunless
            </h2>
            <p class="text-slate-500 mt-1">{{ $affiliate->email }} · {{ $affiliate->phone }} · daftar {{ $affiliate->created_at->translatedFormat('d M Y') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($affiliate->status !== 'active')
                <form method="POST" action="{{ route('admin.affiliate.status', $affiliate) }}">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    <button class="admin-btn px-4 py-2 rounded-lg text-sm font-semibold">{{ $affiliate->status === 'pending' ? 'Setujui' : 'Aktifkan' }}</button>
                </form>
            @endif
            @if ($affiliate->status === 'pending')
                <form method="POST" action="{{ route('admin.affiliate.status', $affiliate) }}" onsubmit="const r = prompt('Alasan penolakan (opsional, dikirim ke email affiliate):'); if (r === null) return false; this.reason.value = r;">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <input type="hidden" name="reason" value="">
                    <button class="admin-btn-danger px-4 py-2 rounded-lg text-sm font-semibold">Tolak</button>
                </form>
            @endif
            @if ($affiliate->status === 'active')
                <form method="POST" action="{{ route('admin.affiliate.status', $affiliate) }}" onsubmit="return confirm('Nonaktifkan affiliate ini? Pembayaran baru dari referralnya tidak akan menghasilkan komisi selama nonaktif.')">
                    @csrf
                    <input type="hidden" name="status" value="suspended">
                    <button class="px-4 py-2 rounded-lg text-sm font-semibold border border-slate-300 text-slate-700 hover:bg-white">Nonaktifkan</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.affiliate.reset-password', $affiliate) }}" onsubmit="return confirm('Buat password baru untuk affiliate ini?')">
                @csrf
                <button class="px-4 py-2 rounded-lg text-sm border border-slate-300 text-slate-700 hover:bg-white">Reset Password</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-admin.stat-card label="Klik / Referral" :value="number_format($affiliate->clicks, 0, ',', '.').' / '.$referrals->count()" value-class="text-slate-900" />
        <x-admin.stat-card label="Siap dicairkan" :value="$rupiah($balance['available'])" />
        <x-admin.stat-card label="Dalam pengajuan" :value="$rupiah($balance['requested'])" value-class="text-amber-600" />
        <x-admin.stat-card label="Sudah dibayar" :value="$rupiah($balance['paid'])" value-class="text-slate-900" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.affiliate.update', $affiliate) }}" class="admin-card p-5 space-y-4">
                @csrf
                <h3 class="font-semibold text-slate-900">Pengaturan Affiliate</h3>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kode referral</label>
                    <input name="code" value="{{ old('code', $affiliate->code) }}" class="admin-input font-mono uppercase" required>
                    <p class="text-xs text-slate-500 mt-1 break-all">{{ $affiliate->referralUrl() }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Komisi khusus (%)</label>
                    <input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ old('commission_rate', $affiliate->commission_rate) }}" class="admin-input" placeholder="Default {{ $service->formatRate($settings['default_rate']) }}">
                    <p class="text-xs text-slate-500 mt-1">Kosongkan untuk mengikuti default. Berlaku untuk pembayaran berikutnya. Saat ini: <strong>{{ $service->formatRate($rate) }}</strong></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Catatan internal</label>
                    <textarea name="admin_note" rows="3" class="admin-input">{{ old('admin_note', $affiliate->admin_note) }}</textarea>
                </div>
                <button class="admin-btn w-full py-2.5 rounded-lg font-semibold">Simpan</button>
            </form>

            <div id="rekening" class="admin-card p-5 text-sm space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-semibold text-slate-900">Rekening Pencairan</h3>
                    <x-admin.badge :status="$affiliate->bankStatusLabel()" :variant="$affiliate->bankStatusVariant()" />
                </div>

                @if ($affiliate->hasBankAccount())
                    <div>
                        <p>{{ $affiliate->bank_name }}</p>
                        <p class="font-mono">{{ $affiliate->bank_account_number }}</p>
                        <p class="text-slate-500">a.n. {{ $affiliate->bank_account_name }}</p>
                    </div>
                @else
                    <p class="text-slate-500">Belum diisi.</p>
                @endif

                @if ($affiliate->bank_submitted_at)
                    <p class="text-xs text-slate-500">
                        Diajukan {{ $affiliate->bank_submitted_at->translatedFormat('d M Y H:i') }}
                        @if ($affiliate->bank_verified_at) · diverifikasi {{ $affiliate->bank_verified_at->translatedFormat('d M Y H:i') }} @endif
                    </p>
                @endif

                @if ($affiliate->bank_verification_status === 'rejected' && $affiliate->bank_verification_note)
                    <p class="p-2 rounded-lg bg-rose-50 text-rose-700 text-xs">Alasan ditolak: {{ $affiliate->bank_verification_note }}</p>
                @endif

                @foreach (\App\Models\Affiliate::DOCUMENTS as $type => $label)
                    <div>
                        <p class="text-xs font-medium text-slate-600 mb-1">{{ $label }}</p>
                        @if ($affiliate->documentPath($type))
                            <a href="{{ route('admin.affiliate.document', [$affiliate, $type]) }}" target="_blank" class="block rounded-lg overflow-hidden border border-slate-200 hover:border-emerald-400">
                                @if ($affiliate->documentIsPdf($type))
                                    <span class="flex items-center gap-2 p-3 text-emerald-700"><i data-lucide="file-text" class="w-4 h-4"></i> Buka PDF</span>
                                @else
                                    <img src="{{ route('admin.affiliate.document', [$affiliate, $type]) }}" alt="{{ $label }}" class="w-full max-h-56 object-contain bg-slate-50">
                                @endif
                            </a>
                        @else
                            <p class="text-xs text-slate-400">Belum diupload.</p>
                        @endif
                    </div>
                @endforeach

                @if ($affiliate->bank_verification_status !== 'unsubmitted')
                    <div class="pt-2 border-t border-slate-100 space-y-2">
                        @if ($affiliate->bank_verification_status !== 'verified')
                            <form method="POST" action="{{ route('admin.affiliate.bank.verify', $affiliate) }}" onsubmit="return confirm('Nama di KTP, buku tabungan, dan rekening sudah sesuai?')">
                                @csrf
                                <button class="admin-btn w-full py-2 rounded-lg font-semibold">Verifikasi Rekening</button>
                            </form>
                        @endif
                        @if ($affiliate->bank_verification_status !== 'rejected')
                            <form method="POST" action="{{ route('admin.affiliate.bank.reject', $affiliate) }}" onsubmit="const r = prompt('Alasan penolakan (dikirim ke affiliate), misal: foto KTP buram / nama tidak sesuai:'); if (!r) return false; this.note.value = r;">
                                @csrf
                                <input type="hidden" name="note" value="">
                                <button class="w-full py-2 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50">
                                    {{ $affiliate->bank_verification_status === 'verified' ? 'Cabut Verifikasi' : 'Tolak' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card overflow-x-auto">
                <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">Komisi ({{ $commissions->count() }} terbaru)</div>
                <table class="w-full text-sm min-w-[640px]">
                    <tbody>
                        @forelse ($commissions as $commission)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 text-slate-500 whitespace-nowrap">{{ $commission->created_at->translatedFormat('d M Y') }}</td>
                                <td class="p-3">
                                    <p class="font-medium">{{ $commission->description }}</p>
                                    <p class="text-xs text-slate-500">{{ $commission->purposeLabel() }} · {{ $rupiah($commission->base_amount) }} × {{ $service->formatRate((float) $commission->rate) }}</p>
                                </td>
                                <td class="p-3 text-right font-semibold whitespace-nowrap">{{ $rupiah($commission->amount) }}</td>
                                <td class="p-3"><x-admin.badge :status="$commission->statusLabel()" :variant="$commission->statusVariant()" /></td>
                                <td class="p-3 text-right">
                                    @if ($commission->status === 'available')
                                        <form method="POST" action="{{ route('admin.affiliate.commissions.cancel', $commission) }}" onsubmit="return confirm('Batalkan komisi ini (misal karena refund)?')">
                                            @csrf
                                            <button class="text-xs text-rose-700 hover:underline">Batalkan</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="p-6 text-center text-slate-500">Belum ada komisi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-card overflow-x-auto">
                <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">Pelanggan Referral</div>
                <table class="w-full text-sm min-w-[560px]">
                    <tbody>
                        @forelse ($referrals as $user)
                            <tr class="border-t border-slate-100">
                                <td class="p-3">
                                    <p class="font-medium">{{ $user->madrasah_name ?: $user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </td>
                                <td class="p-3 text-slate-500">{{ $user->referred_at?->translatedFormat('d M Y') }}</td>
                                <td class="p-3 text-right">{{ $user->rdm_instances_count }} RDM</td>
                            </tr>
                        @empty
                            <tr><td class="p-6 text-center text-slate-500">Belum ada pelanggan referral.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-card overflow-x-auto">
                <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">Riwayat Pencairan</div>
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($payouts as $payout)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 text-slate-500">{{ $payout->created_at->translatedFormat('d M Y') }}</td>
                                <td class="p-3 font-semibold">{{ $rupiah($payout->amount) }}</td>
                                <td class="p-3"><x-admin.badge :status="$payout->statusLabel()" :variant="$payout->statusVariant()" /></td>
                                <td class="p-3 text-xs text-slate-500">{{ $payout->transfer_reference }} {{ $payout->admin_note }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-6 text-center text-slate-500">Belum ada pencairan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
