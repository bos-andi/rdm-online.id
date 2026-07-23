<footer class="bg-emerald-950 text-emerald-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-14">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
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
                    <li><a href="#fitur" class="hover:text-white transition-colors">Fitur</a></li>
                    <li><a href="#harga" class="hover:text-white transition-colors">Harga</a></li>
                    <li><a href="{{ route('deploy') }}" class="hover:text-white transition-colors">Deploy RDM</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-white mb-4">Kontak</h4>
                <ul class="space-y-2.5 text-sm text-emerald-300/80">
                    <li class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        support@rdm-online.id
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        WhatsApp Support
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-emerald-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-emerald-400/70">
            <p>&copy; {{ date('Y') }} PT ANDIDEV KREATIF DIGITAL. All rights reserved.</p>
            <p>RDM-ONLINE.ID — Dikembangkan oleh <span class="text-emerald-300/90 font-medium">PT ANDIDEV KREATIF DIGITAL</span></p>
        </div>
    </div>
</footer>
