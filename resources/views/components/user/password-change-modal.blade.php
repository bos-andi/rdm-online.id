@if (auth()->check() && auth()->user()->must_change_password && ! session('password_prompt_dismissed'))
    <div id="password-change-modal" class="fixed inset-0 z-[60]">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-amber-200">
                <div class="px-6 pt-6 pb-4 border-b border-amber-100 bg-amber-50">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-amber-100 text-amber-700 shrink-0">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-amber-900">Ganti Password Anda</h3>
                            <p class="text-sm text-amber-800 mt-1">
                                Anda masih memakai password default dari email. Demi keamanan, segera buat password baru.
                            </p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('user.profile.password') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label for="modal-password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input
                            id="modal-password"
                            name="password"
                            type="password"
                            required
                            minlength="8"
                            class="app-input"
                            placeholder="Min. 8 karakter"
                            autocomplete="new-password"
                        >
                        @error('password')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="modal-password-confirm" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                        <input
                            id="modal-password-confirm"
                            name="password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            class="app-input"
                            placeholder="Ulangi password baru"
                            autocomplete="new-password"
                        >
                    </div>

                    <button type="submit" class="app-btn w-full py-2.5 rounded-xl font-semibold">
                        Simpan Password Baru
                    </button>
                </form>

                <div class="px-6 pb-6">
                    <form method="POST" action="{{ route('user.profile.password.dismiss') }}">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                            Nanti saja
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('overflow-hidden');
        if (window.lucide) lucide.createIcons();
    });
    </script>
    @endpush
@endif
