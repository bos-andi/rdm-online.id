@extends('layouts.admin')

@section('title', 'Manajemen Pelanggan')

@section('content')
    <x-admin.page-header title="Manajemen Pelanggan" />

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('customer_created'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            @if (session('customer_created.reset'))
                <p class="font-semibold">Password pelanggan berhasil direset.</p>
            @else
                <p class="font-semibold">Akun pelanggan berhasil dibuat.</p>
            @endif
            <p class="mt-2">Email: <strong>{{ session('customer_created.email') }}</strong></p>
            <p>Password: <strong>{{ session('customer_created.password') }}</strong></p>
            @if (session('customer_created.emailed'))
                <p class="mt-2 text-emerald-700">Kredensial juga telah dikirim ke email pelanggan.</p>
            @endif
            <p class="mt-2 text-emerald-700">Simpan kredensial ini — password tidak ditampilkan lagi.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-2 admin-card p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Buat Akun Pelanggan</h2>
            <form method="POST" action="{{ route('admin.pelanggan.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama PIC</label>
                    <input name="name" value="{{ old('name') }}" required class="admin-input" placeholder="Ustadz Ahmad">
                    @error('name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Madrasah</label>
                    <input name="madrasah_name" value="{{ old('madrasah_name') }}" required class="admin-input" placeholder="MI Nurul Huda">
                    @error('madrasah_name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email Login</label>
                    <input name="email" type="email" value="{{ old('email') }}" required class="admin-input" placeholder="admin@madrasah.sch.id">
                    @error('email')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Jenjang</label>
                    <select name="jenjang" class="admin-input" required>
                        @foreach (['RA', 'MI', 'MTs', 'MA'] as $j)
                            <option value="{{ $j }}" @selected(old('jenjang') === $j)>{{ $j }}</option>
                        @endforeach
                    </select>
                    @error('jenjang')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password (opsional)</label>
                    <input name="password" type="text" class="admin-input" placeholder="Kosongkan untuk generate otomatis">
                    @error('password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="admin-btn w-full py-2.5 rounded-lg font-semibold">
                    Buat Akun
                </button>
            </form>
        </div>

        <div class="lg:col-span-3 space-y-6">
            <div class="admin-card overflow-hidden">
                <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">Akun Pelanggan</div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-t border-slate-100">
                            <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Email</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Jenjang</th>
                            <th class="p-3 text-left font-semibold text-slate-700">RDM</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 font-medium">{{ $customer->madrasah_name ?? $customer->name }}</td>
                                <td class="p-3 text-slate-500">{{ $customer->email }}</td>
                                <td class="p-3">{{ $customer->jenjang ?? '—' }}</td>
                                <td class="p-3">{{ $customer->rdm_instances_count }}</td>
                                <td class="p-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <form method="POST" action="{{ route('admin.pelanggan.reset-password', $customer) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs text-emerald-700 hover:underline">Reset Password</button>
                                        </form>
                                        @if ($customer->rdm_instances_count === 0)
                                            <button
                                                type="button"
                                                class="text-xs text-rose-800 hover:underline font-semibold"
                                                data-open-delete-customer
                                                data-email="{{ $customer->email }}"
                                                data-label="{{ $customer->madrasah_name ?? $customer->name }}"
                                                data-action="{{ route('admin.pelanggan.destroy', $customer) }}"
                                            >
                                                Hapus
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400" title="Hapus RDM di Manajemen RDM terlebih dahulu">Hapus</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">Belum ada akun pelanggan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-card overflow-hidden">
                <div class="admin-card-header px-4 py-3 font-semibold text-slate-700">Instance RDM</div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-t border-slate-100">
                            <th class="p-3 text-left font-semibold text-slate-700">Madrasah</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Domain</th>
                            <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($instances as $rdm)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 font-medium">{{ $rdm->madrasah_name }}</td>
                                <td class="p-3 text-emerald-700">{{ $rdm->fqdn }}</td>
                                <td class="p-3"><x-admin.badge :status="ucfirst($rdm->status)" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-8 text-center text-slate-400">Belum ada instance RDM.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="delete-customer-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="delete-customer-backdrop"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-rose-200">
                <div class="px-6 pt-6 pb-4 border-b border-rose-100 bg-rose-50">
                    <h3 class="font-bold text-rose-900">Hapus Akun Pelanggan</h3>
                    <p class="text-sm text-rose-700 mt-1" id="delete-customer-label"></p>
                </div>

                <form method="POST" id="delete-customer-form" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <p class="text-sm text-slate-600">
                        Akun login pelanggan akan dihapus permanen. Riwayat pembayaran tetap tersimpan di billing.
                    </p>
                    <div>
                        <label for="delete-customer-confirm" class="block text-sm font-medium text-slate-700 mb-1">
                            Ketik email untuk konfirmasi: <span class="font-mono text-rose-700" id="delete-customer-email-label"></span>
                        </label>
                        <input type="text" id="delete-customer-confirm" name="confirm_email" class="admin-input font-mono" autocomplete="off" required>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="cancel-delete-customer" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50">Batal</button>
                        <button type="submit" class="flex-1 admin-btn-danger py-2.5 rounded-xl font-semibold">Hapus Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-customer-modal');
    const form = document.getElementById('delete-customer-form');
    const labelEl = document.getElementById('delete-customer-label');
    const emailLabel = document.getElementById('delete-customer-email-label');
    const confirmInput = document.getElementById('delete-customer-confirm');

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-open-delete-customer]').forEach((button) => {
        button.addEventListener('click', () => {
            labelEl.textContent = button.dataset.label;
            emailLabel.textContent = button.dataset.email;
            form.action = button.dataset.action;
            confirmInput.value = '';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            confirmInput.focus();
        });
    });

    document.getElementById('cancel-delete-customer')?.addEventListener('click', closeModal);
    document.getElementById('delete-customer-backdrop')?.addEventListener('click', closeModal);
});
</script>
@endpush
