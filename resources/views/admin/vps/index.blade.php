@extends('layouts.admin')

@section('title', 'Server VPS')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Manajemen Server VPS</h2>
            <p class="text-slate-500 mt-1">Admin memilih server deploy aktif — RDM baru mengikuti server yang dipilih</p>
        </div>
        <button type="button" id="open-vps-modal" class="admin-btn inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold shrink-0">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Tambah Server VPS
        </button>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="admin-card p-5 mb-6 border-blue-200 bg-blue-50/50">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Server Deploy Aktif (dipilih admin)</p>
            @if ($activeServer)
                <p class="text-lg font-bold text-slate-900 mt-1">
                    {{ $activeServer->code }}
                    <span class="text-slate-500 font-normal text-base">— {{ $activeServer->location }}</span>
                </p>
                <p class="text-sm text-slate-600 mt-0.5">
                    IP <span class="font-mono">{{ $activeServer->ip_address }}</span>
                    • Path <span class="font-mono">{{ $activeServer->deploy_base_path ?? '/var/www/rdm-online' }}</span>
                </p>
                <p class="text-xs text-slate-500 mt-2">
                    Pelanggan <strong>baru</strong> deploy ke server ini. DNS A record subdomain baru → IP ini.
                    RDM yang sudah ada <strong>tidak dipindahkan</strong>.
                </p>
            @else
                <p class="text-slate-600 mt-1">Belum ada server deploy aktif. Klik <strong>Aktifkan untuk Deploy</strong> pada salah satu server.</p>
            @endif
        </div>
    </div>

    <div class="admin-card p-4 mb-6 text-sm text-slate-600 border-amber-100 bg-amber-50/50">
        <p class="font-medium text-slate-800 mb-1">Cara hubungkan VPS remote</p>
        <ol class="list-decimal list-inside space-y-1">
            <li>Tambah server → centang <strong>Buat SSH key otomatis</strong></li>
            <li><strong>Salin public key</strong> yang muncul → paste di portal Biznet (Add SSH Key)</li>
            <li>Buat VPS & pilih key tersebut, lalu bootstrap di VPS (<code class="text-xs bg-white px-1 rounded">rdm-remote-bootstrap.sh</code>)</li>
            <li>Klik <strong>Tes SSH</strong> → <strong>Sync Deploy</strong> → <strong>Aktifkan untuk Deploy</strong></li>
        </ol>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($servers as $vps)
            <div @class([
                'admin-card p-5',
                'ring-2 ring-blue-400 ring-offset-2' => $vps['is_primary'],
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-slate-900">{{ $vps['name'] }}</h3>
                            @if ($vps['is_local'])
                                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">Panel Lokal</span>
                            @endif
                            @if ($vps['is_primary'])
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium">Deploy Aktif</span>
                            @endif
                            @unless ($vps['deploy_ready'])
                                <span class="text-xs px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 font-medium">Belum Siap</span>
                            @endunless
                        </div>
                        <p class="text-slate-500 text-sm">{{ $vps['location'] }} • {{ $vps['ip'] }}</p>
                        <p class="text-slate-500 text-sm mt-1">{{ $vps['spec'] }}</p>
                        @unless ($vps['is_local'])
                            <p class="text-xs text-slate-400 mt-1">SSH: {{ $vps['ssh_user'] ?? 'root' }}
                                @if ($vps['ssh_key_name'])
                                    • Key: {{ $vps['ssh_key_name'] }}
                                @endif
                            </p>
                        @endunless
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                        <span>Kapasitas RDM</span>
                        <span>{{ $vps['count'] }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full">
                        <div @class([
                            'h-2 rounded-full',
                            'bg-emerald-500' => $vps['load'] < 70,
                            'bg-amber-500' => $vps['load'] >= 70 && $vps['load'] < 90,
                            'bg-rose-500' => $vps['load'] >= 90,
                        ]) style="width: {{ $vps['load'] }}%"></div>
                    </div>
                </div>

                @unless ($vps['is_local'])
                    <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <p class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Public Key → paste ke panel VPS</p>
                            @if ($vps['ssh_public_key'])
                                <button type="button"
                                    class="copy-pubkey text-xs px-2 py-1 rounded-md bg-white border border-slate-200 text-slate-600 hover:bg-slate-100"
                                    data-key="{{ $vps['ssh_public_key'] }}">
                                    Salin
                                </button>
                            @endif
                        </div>
                        @if ($vps['ssh_public_key'])
                            <code class="block text-[10px] leading-relaxed text-slate-600 break-all font-mono">{{ $vps['ssh_public_key'] }}</code>
                            <p class="text-xs text-slate-500 mt-2">Belum connect? Buka <strong>Console</strong> di panel VPS, login sebagai <code>{{ $vps['ssh_user'] ?? 'root' }}</code>, lalu:</p>
                            <button type="button"
                                class="copy-console-cmd mt-1 text-xs px-2 py-1 rounded-md bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100"
                                data-user="{{ $vps['ssh_user'] ?? 'root' }}"
                                data-key="{{ $vps['ssh_public_key'] }}">
                                Salin perintah Console
                            </button>
                        @else
                            <p class="text-xs text-amber-700">Belum ada key. Klik <strong>Kelola SSH</strong> → buat key baru.</p>
                        @endif
                    </div>
                @endunless

                <div class="mt-4 flex flex-wrap gap-2">
                    @unless ($vps['is_local'])
                        <button type="button"
                            class="open-ssh-modal text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50"
                            data-id="{{ $vps['id'] }}"
                            data-code="{{ $vps['name'] }}"
                            data-key-name="{{ $vps['ssh_key_name'] ?? '' }}"
                            data-ssh-user="{{ $vps['ssh_user'] ?? 'root' }}"
                            data-ssh-port="{{ $vps['ssh_port'] ?? 22 }}"
                            data-public-key="{{ $vps['ssh_public_key'] ?? '' }}">
                            Kelola SSH
                        </button>
                        <form method="POST" action="{{ route('admin.vps.generate-key', $vps['id']) }}">
                            @csrf
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                onclick="return confirm('Buat key baru? Key lama tidak bisa dipakai lagi kecuali masih terdaftar di VPS.')">
                                Key Baru
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.vps.sync', $vps['id']) }}">
                            @csrf
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                                Sync Deploy
                            </button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('admin.vps.test', $vps['id']) }}">
                        @csrf
                        <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                            Tes SSH
                        </button>
                    </form>
                    @if ($vps['is_primary'])
                        <span class="text-xs text-blue-600 font-medium py-1.5">Server deploy aktif</span>
                    @else
                        <form method="POST" action="{{ route('admin.vps.primary', $vps['id']) }}">
                            @csrf
                            <button type="submit" class="admin-btn-sm text-xs px-3 py-1.5 rounded-lg">
                                Aktifkan untuk Deploy
                            </button>
                        </form>
                    @endif
                    @unless ($vps['is_local'] || $vps['is_primary'])
                        <form method="POST" action="{{ route('admin.vps.destroy', $vps['id']) }}" onsubmit="return confirm('Hapus server {{ $vps['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50">
                                Hapus
                            </button>
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <div class="admin-card p-8 text-center text-slate-400 sm:col-span-2">
                Belum ada server. Klik <strong>Tambah Server VPS</strong> untuk memulai.
            </div>
        @endforelse
    </div>

    {{-- Modal Tambah VPS --}}
    <div id="vps-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="vps-modal-backdrop"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto border border-slate-200">
                <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-slate-900">Tambah Server VPS</h3>
                        <p class="text-slate-500 text-sm">Remote worker untuk deploy RDM</p>
                    </div>
                    <button type="button" id="close-vps-modal" class="p-2 rounded-lg hover:bg-slate-100 text-slate-400">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.vps.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Server</label>
                        <input name="code" value="{{ old('code') }}" required class="admin-input uppercase" placeholder="ID-02">
                        @error('code')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Lokasi</label>
                            <input name="location" value="{{ old('location') }}" required class="admin-input" placeholder="Jakarta">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">IP Address</label>
                            <input name="ip_address" value="{{ old('ip_address') }}" required class="admin-input" placeholder="203.0.113.10">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Max RDM</label>
                        <input name="max_rdm_capacity" type="number" min="1" value="{{ old('max_rdm_capacity', 50) }}" required class="admin-input">
                        <p class="text-xs text-slate-400 mt-1">vCPU & RAM terdeteksi otomatis setelah Tes SSH</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Path Deploy RDM</label>
                        <input name="deploy_base_path" value="{{ old('deploy_base_path', '/var/www/rdm-online') }}" class="admin-input">
                    </div>

                    <div class="border-t border-slate-100 pt-4 space-y-3">
                        <p class="text-sm font-medium text-slate-800">SSH Key</p>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Key</label>
                            <input name="ssh_key_name" value="{{ old('ssh_key_name') }}" class="admin-input" placeholder="biznet-neo-lite">
                            <p class="text-xs text-slate-400 mt-1">Label untuk portal Biznet / referensi Anda</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">SSH User</label>
                                <input name="ssh_user" value="{{ old('ssh_user', 'root') }}" class="admin-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">SSH Port</label>
                                <input name="ssh_port" type="number" value="{{ old('ssh_port', 22) }}" class="admin-input">
                            </div>
                        </div>
                        <label class="flex items-start gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100 cursor-pointer">
                            <input type="checkbox" name="generate_ssh_key" value="1" class="mt-1 rounded border-slate-300" {{ old('generate_ssh_key', '1') ? 'checked' : '' }}>
                            <span class="text-sm text-slate-700">
                                <strong>Buat SSH key otomatis</strong> — public key ditampilkan setelah simpan, paste ke Biznet
                            </span>
                        </label>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Atau paste Private Key</label>
                            <textarea name="ssh_private_key" rows="4" class="admin-input font-mono text-xs" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...">{{ old('ssh_private_key') }}</textarea>
                            <p class="text-xs text-slate-400 mt-1">Opsional jika key sudah ada. Public key dihitung otomatis.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Catatan</label>
                        <textarea name="notes" rows="2" class="admin-input" placeholder="Opsional">{{ old('notes') }}</textarea>
                    </div>

                    <p class="text-xs text-slate-500 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        Setelah SSH & Sync selesai, klik <strong>Aktifkan untuk Deploy</strong> di kartu server agar pelanggan baru deploy ke VPS ini.
                    </p>

                    <div class="flex gap-3 pt-2">
                        <button type="button" id="cancel-vps-modal" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50">Batal</button>
                        <button type="submit" class="flex-1 admin-btn py-2.5 rounded-xl font-semibold">Simpan Server</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Kelola SSH --}}
    <div id="ssh-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="ssh-modal-backdrop"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto border border-slate-200">
                <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-slate-900">Kelola SSH — <span id="ssh-modal-code"></span></h3>
                        <p class="text-slate-500 text-sm">Update key & koneksi</p>
                    </div>
                    <button type="button" id="close-ssh-modal" class="p-2 rounded-lg hover:bg-slate-100 text-slate-400">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form id="ssh-form" method="POST" class="p-6 space-y-4">
                    @csrf

                    <div id="ssh-public-display" class="hidden p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <p class="text-xs font-semibold text-slate-700">Public Key (paste ke Biznet)</p>
                            <button type="button" id="ssh-copy-btn" class="text-xs px-2 py-1 rounded-md bg-white border border-slate-200">Salin</button>
                        </div>
                        <code id="ssh-public-text" class="block text-[10px] break-all font-mono text-slate-600"></code>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Key</label>
                        <input name="ssh_key_name" id="ssh-key-name" class="admin-input">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SSH User</label>
                            <input name="ssh_user" id="ssh-user" required class="admin-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SSH Port</label>
                            <input name="ssh_port" id="ssh-port" type="number" required class="admin-input" value="22">
                        </div>
                    </div>
                    <label class="flex items-start gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100 cursor-pointer">
                        <input type="checkbox" name="generate_ssh_key" value="1" class="mt-1 rounded border-slate-300">
                        <span class="text-sm text-slate-700"><strong>Ganti dengan key baru</strong></span>
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Atau paste Private Key baru</label>
                        <textarea name="ssh_private_key" rows="4" class="admin-input font-mono text-xs" placeholder="Kosongkan jika tidak ganti key"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" id="cancel-ssh-modal" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50">Batal</button>
                        <button type="submit" class="flex-1 admin-btn py-2.5 rounded-xl font-semibold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const copyText = async (text, btn) => {
        try {
            await navigator.clipboard.writeText(text);
            const orig = btn.textContent;
            btn.textContent = 'Tersalin!';
            setTimeout(() => { btn.textContent = orig; }, 1500);
        } catch {
            alert('Gagal menyalin. Salin manual dari teks public key.');
        }
    };

    document.querySelectorAll('.copy-pubkey').forEach(btn => {
        btn.addEventListener('click', () => copyText(btn.dataset.key, btn));
    });

    document.querySelectorAll('.copy-console-cmd').forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.key;
            const user = btn.dataset.user || 'root';
            const cmd = `mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '${key}' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && echo "OK - key terpasang untuk ${user}"`;
            copyText(cmd, btn);
        });
    });

    const vpsModal = document.getElementById('vps-modal');
    const openVps = () => { vpsModal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); if (window.lucide) lucide.createIcons(); };
    const closeVps = () => { vpsModal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); };

    document.getElementById('open-vps-modal')?.addEventListener('click', openVps);
    document.getElementById('close-vps-modal')?.addEventListener('click', closeVps);
    document.getElementById('cancel-vps-modal')?.addEventListener('click', closeVps);
    document.getElementById('vps-modal-backdrop')?.addEventListener('click', closeVps);

    const sshModal = document.getElementById('ssh-modal');
    const sshForm = document.getElementById('ssh-form');
    const sshPublicDisplay = document.getElementById('ssh-public-display');
    const sshPublicText = document.getElementById('ssh-public-text');
    const sshCopyBtn = document.getElementById('ssh-copy-btn');

    const openSsh = (btn) => {
        const id = btn.dataset.id;
        sshForm.action = `/admin/vps/${id}/ssh`;
        document.getElementById('ssh-modal-code').textContent = btn.dataset.code;
        document.getElementById('ssh-key-name').value = btn.dataset.keyName || btn.dataset.code;
        document.getElementById('ssh-user').value = btn.dataset.sshUser || 'root';
        document.getElementById('ssh-port').value = btn.dataset.sshPort || '22';

        if (btn.dataset.publicKey) {
            sshPublicDisplay.classList.remove('hidden');
            sshPublicText.textContent = btn.dataset.publicKey;
            sshCopyBtn.onclick = () => copyText(btn.dataset.publicKey, sshCopyBtn);
        } else {
            sshPublicDisplay.classList.add('hidden');
        }

        sshModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        if (window.lucide) lucide.createIcons();
    };

    const closeSsh = () => { sshModal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); };

    document.querySelectorAll('.open-ssh-modal').forEach(btn => btn.addEventListener('click', () => openSsh(btn)));
    document.getElementById('close-ssh-modal')?.addEventListener('click', closeSsh);
    document.getElementById('cancel-ssh-modal')?.addEventListener('click', closeSsh);
    document.getElementById('ssh-modal-backdrop')?.addEventListener('click', closeSsh);

    @if ($errors->any() || old('code'))
        openVps();
    @endif
});
</script>
@endpush
