<footer class="bg-emerald-950 text-emerald-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-14">
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-10">
            <div class="sm:col-span-2">
                <div class="mb-4">
                    <x-brand.logo height="h-10" />
                </div>
                <p class="text-emerald-300/80 text-sm leading-relaxed max-w-md">
                    Platform hosting RDM (Rapor Digital Madrasah) dengan deploy otomatis.
                    Dari RDMhosting siap pakai — tinggal satu klik, madrasah Anda langsung online.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-white mb-4">Produk</h4>
                <ul class="space-y-2.5 text-sm text-emerald-300/80">
                    <li><a href="{{ route('landing') }}#fitur" class="hover:text-white transition-colors">Fitur</a></li>
                    <li><a href="{{ route('landing') }}#harga" class="hover:text-white transition-colors">Harga</a></li>
                    <li><a href="{{ route('deploy') }}" class="hover:text-white transition-colors">Deploy RDM</a></li>
                    <li><a href="{{ route('checkout') }}" class="hover:text-white transition-colors">Checkout</a></li>
                    <li><a href="{{ route('affiliate.program') }}" class="hover:text-white transition-colors">Program Affiliate</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-white mb-4">Perusahaan</h4>
                <ul class="space-y-2.5 text-sm text-emerald-300/80">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-white transition-colors">Tentang Kami</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-white transition-colors">Syarat &amp; Ketentuan</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-white transition-colors">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('pages.refund') }}" class="hover:text-white transition-colors">Kebijakan Pengembalian Dana</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-white mb-4">Kontak</h4>
                <ul class="space-y-2.5 text-sm text-emerald-300/80">
                    <li>
                        <a href="mailto:{{ config('company.email') }}" class="flex items-center gap-2 hover:text-white transition-colors">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            {{ config('company.email') }}
                        </a>
                    </li>
                    <li>
                        <a href="https://wa.me/{{ config('company.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-white transition-colors">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                            WhatsApp Support
                        </a>
                    </li>
                    <li>
                        <a href="{{ config('company.telegram') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-white transition-colors">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Grup Telegram
                        </a>
                    </li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-white transition-colors">Halaman Kontak</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-emerald-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-emerald-400/70">
            <p>&copy; {{ date('Y') }} PT ANDIDEV KREATIF DIGITAL. All rights reserved.</p>
            <p>RDM-ONLINE.ID — Dikembangkan oleh <span class="text-emerald-300/90 font-medium">PT ANDIDEV KREATIF DIGITAL</span></p>
        </div>
    </div>
</footer>
