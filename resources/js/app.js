import { createIcons, icons } from 'lucide';
import QRCode from 'qrcode';

function initIcons() {
    createIcons({ icons });
}

function initPasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        if (btn.dataset.bound) {
            return;
        }

        btn.dataset.bound = '1';
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            const icon = btn.querySelector('[data-lucide]');
            if (icon) {
                icon.setAttribute('data-lucide', show ? 'eye-off' : 'eye');
                initIcons();
            }

            btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        });
    });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const PROGRESS_CIRCUMFERENCE = 213.6;

function getDeployProgressElements(root) {
    return {
        pct: root.querySelector('[data-progress-pct]'),
        ring: root.querySelector('[data-progress-ring]'),
        label: root.querySelector('[data-progress-label]'),
        steps: root.querySelector('[data-progress-steps]'),
        done: root.querySelector('[data-progress-done]'),
        doneMessage: root.querySelector('[data-progress-done-message]'),
        url: root.querySelector('[data-progress-url]'),
        error: root.querySelector('[data-progress-error]'),
        fqdn: root.querySelector('[data-progress-fqdn]'),
    };
}

function resetDeployProgressUI(root) {
    const el = getDeployProgressElements(root);
    if (el.pct) el.pct.textContent = '0%';
    if (el.ring) el.ring.style.strokeDashoffset = String(PROGRESS_CIRCUMFERENCE);
    if (el.label) el.label.textContent = 'Memulai...';
    if (el.steps) el.steps.innerHTML = '';
    if (el.done) el.done.classList.add('hidden');
    if (el.error) {
        el.error.textContent = '';
        el.error.classList.add('hidden');
    }
}

function getProgressStepLabels(root) {
    const raw = root?.dataset?.progressStepLabels;
    if (!raw) {
        return [];
    }

    try {
        return JSON.parse(raw);
    } catch {
        return [];
    }
}

function renderProgressSteps(container, steps) {
    if (!container) {
        return;
    }

    container.innerHTML = (steps ?? []).map((step) => {
        const icon = step.status === 'completed' ? 'check-circle' : step.status === 'running' ? 'loader' : 'circle';
        const color = step.status === 'completed' ? 'text-emerald-600' : step.status === 'running' ? 'text-amber-500' : 'text-gray-300';

        return `<div class="flex items-center gap-2 ${color}"><i data-lucide="${icon}" class="w-4 h-4 ${step.status === 'running' ? 'animate-spin' : ''}"></i><span>${step.label}</span></div>`;
    }).join('');

    initIcons();
}

function normalizeProgressData(data, root) {
    if (!data.destroyed && data.status !== 'destroyed') {
        return data;
    }

    const completedSteps = (data.steps ?? []).length
        ? data.steps.map((step) => ({ ...step, status: 'completed' }))
        : getProgressStepLabels(root).map((label) => ({ label, status: 'completed' }));

    return {
        ...data,
        progress: 100,
        destroyed: true,
        status: 'destroyed',
        steps: completedSteps,
    };
}

function updateDeployProgressUI(root, data, pollState) {
    data = normalizeProgressData(data, root);
    const el = getDeployProgressElements(root);
    const pct = data.progress ?? 0;

    if (el.pct) el.pct.textContent = `${pct}%`;
    if (el.ring) {
        el.ring.style.strokeDashoffset = String(PROGRESS_CIRCUMFERENCE - (pct / 100) * PROGRESS_CIRCUMFERENCE);
    }

    const isDone = data.destroyed || data.status === 'destroyed' || data.status === 'active';
    const current = isDone ? null : data.steps?.find((step) => step.status === 'running');

    if (el.label) {
        el.label.textContent = isDone ? 'Selesai' : (current?.label ?? 'Memproses...');
    }

    renderProgressSteps(el.steps, data.steps);

    if (data.status === 'active') {
        if (pollState?.timer) clearInterval(pollState.timer);
        if (el.done) el.done.classList.remove('hidden');
        if (el.url) {
            el.url.href = data.url;
            el.url.textContent = `Buka ${data.fqdn}`;
        }
    }

    if (data.destroyed || data.status === 'destroyed') {
        if (pollState?.timer) clearInterval(pollState.timer);
        if (el.done) el.done.classList.remove('hidden');
    }

    if (data.status === 'failed') {
        if (pollState?.timer) clearInterval(pollState.timer);
        if (el.error) {
            el.error.textContent = data.error ?? 'Deploy gagal.';
            el.error.classList.remove('hidden');
        }
    }
}

function startDeployStatusPoll(root, statusUrl, pollState = { timer: null }) {
    const poll = async () => {
        try {
            const statusRes = await fetch(statusUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (statusRes.ok) {
                updateDeployProgressUI(root, await statusRes.json(), pollState);
            } else if (statusRes.status === 404) {
                updateDeployProgressUI(root, {
                    status: 'destroyed',
                    destroyed: true,
                    progress: 100,
                }, pollState);
            }
        } catch {
            // retry on next interval
        }
    };

    poll();
    pollState.timer = setInterval(poll, 2000);

    return pollState;
}

function initDeployRetryButtons(options = {}) {
    const modal = document.getElementById(options.modalId || 'admin-deploy-progress-modal');
    if (!modal) return;

    const pollState = { timer: null };

    const close = () => {
        if (pollState.timer) clearInterval(pollState.timer);
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        if (options.reloadOnClose) {
            window.location.reload();
        }
    };

    const openMonitor = (statusUrl, fqdn, title) => {
        resetDeployProgressUI(modal);
        const fqdnEl = modal.querySelector('[data-progress-fqdn]');
        const titleEl = modal.querySelector('[data-progress-title]');
        if (fqdnEl) fqdnEl.textContent = fqdn || '';
        if (titleEl && title) titleEl.textContent = title;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        initIcons();
        if (pollState.timer) clearInterval(pollState.timer);
        startDeployStatusPoll(modal, statusUrl, pollState);
    };

    modal.querySelector('[data-progress-close]')?.addEventListener('click', close);
    modal.querySelector('[data-progress-backdrop]')?.addEventListener('click', close);

    document.querySelectorAll('[data-deploy-monitor]').forEach((button) => {
        button.addEventListener('click', () => {
            openMonitor(button.dataset.statusUrl, button.dataset.fqdn, button.dataset.progressTitle);
        });
    });

    document.querySelectorAll('[data-deploy-retry]').forEach((button) => {
        button.addEventListener('click', async () => {
            const retryUrl = button.dataset.retryUrl;
            const statusUrl = button.dataset.statusUrl;
            const fqdn = button.dataset.fqdn;

            resetDeployProgressUI(modal);
            const fqdnEl = modal.querySelector('[data-progress-fqdn]');
            const titleEl = modal.querySelector('[data-progress-title]');
            if (fqdnEl) fqdnEl.textContent = fqdn || '';
            if (titleEl) titleEl.textContent = 'Deploy Ulang RDM';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            initIcons();

            button.disabled = true;

            try {
                const res = await fetch(retryUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Gagal memulai deploy ulang.');
                }
                startDeployStatusPoll(modal, statusUrl, pollState);
            } catch (error) {
                const errEl = modal.querySelector('[data-progress-error]');
                if (errEl) {
                    errEl.textContent = error.message;
                    errEl.classList.remove('hidden');
                }
            } finally {
                button.disabled = false;
            }
        });
    });
}

window.initDeployRetryButtons = initDeployRetryButtons;

function initDestroyForm(options = {}) {
    const form = document.getElementById(options.formId || 'destroy-form');
    const confirmModal = document.getElementById(options.confirmModalId || 'destroy-modal');
    const progressModal = document.getElementById(options.progressModalId || 'admin-destroy-progress-modal');
    if (!form || !progressModal) return;

    const pollState = { timer: null };

    const closeConfirm = () => {
        confirmModal?.classList.add('hidden');
        if (!progressModal.classList.contains('hidden')) {
            return;
        }
        document.body.classList.remove('overflow-hidden');
    };

    const closeProgress = () => {
        if (pollState.timer) clearInterval(pollState.timer);
        progressModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        if (options.reloadOnClose) {
            window.location.reload();
        }
    };

    progressModal.querySelector('[data-progress-close]')?.addEventListener('click', closeProgress);
    progressModal.querySelector('[data-progress-backdrop]')?.addEventListener('click', closeProgress);

    const openDestroyMonitor = (statusUrl, fqdn) => {
        resetDeployProgressUI(progressModal);
        const fqdnEl = progressModal.querySelector('[data-progress-fqdn]');
        if (fqdnEl) fqdnEl.textContent = fqdn || '';
        progressModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        initIcons();
        if (pollState.timer) clearInterval(pollState.timer);
        startDeployStatusPoll(progressModal, statusUrl, pollState);
    };

    document.querySelectorAll('[data-destroy-monitor]').forEach((button) => {
        button.addEventListener('click', () => {
            openDestroyMonitor(button.dataset.statusUrl, button.dataset.fqdn);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Gagal memulai penghapusan.');
            }

            closeConfirm();
            openDestroyMonitor(data.status_url, data.fqdn);
        } catch (error) {
            const errEl = progressModal.querySelector('[data-progress-error]');
            if (errEl) {
                errEl.textContent = error.message;
                errEl.classList.remove('hidden');
            } else {
                alert(error.message);
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}

window.initDestroyForm = initDestroyForm;

function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('mobile-toggle');

    if (!sidebar || !overlay || !toggle) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    };

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    };

    toggle.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    });

    overlay.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('.menu-item').forEach((item) => {
        item.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });
}

function initDeployWizard() {
    const wizard = document.getElementById('deploy-wizard');
    if (!wizard) return;

    const vaMethods = JSON.parse(wizard.dataset.vaMethods || '[]');
    const formView = document.getElementById('wizard-form-view');
    const qrisView = document.getElementById('wizard-qris-view');
    const progressView = document.getElementById('wizard-progress-view');
    const subdomainInput = document.getElementById('w-subdomain');
    const subdomainStatus = document.getElementById('subdomain-status');
    const emailStatus = document.getElementById('email-status');
    const nextBtn = document.getElementById('wizard-next');
    const prevBtn = document.getElementById('wizard-prev');

    const isGuestMode = () => wizard.dataset.guest === '1';

    let step = 1;
    let subdomainAvailable = false;
    let emailAvailable = wizard.dataset.guest !== '1';
    let preparedInstanceId = null;
    let checkTimer = null;
    let emailCheckTimer = null;
    let pollState = { timer: null };
    let paymentPollTimer = null;

    const packagePrices = JSON.parse(wizard.dataset.packagePrices || '{"monthly":15000,"yearly":50000,"biennial":200000}');
    const packageLabels = { monthly: 'Bulanan', yearly: 'Tahunan', biennial: '2 Tahunan' };

    const formatRp = (value) => `Rp${Number(value).toLocaleString('id-ID')}`;
    const stepHint = document.getElementById('wizard-step-hint');
    const passwordStatus = document.getElementById('password-status');

    const getStepErrors = () => {
        const errors = [];

        if (step === 1) {
            const sub = subdomainInput.value.trim();
            if (!sub || sub.length < 3) {
                errors.push('Subdomain minimal 3 karakter.');
            } else if (!subdomainAvailable) {
                errors.push(subdomainStatus?.textContent || 'Subdomain belum tersedia. Pilih subdomain lain.');
            }
        } else if (step === 2) {
            const nama = document.getElementById('w-nama')?.value.trim();
            const email = document.getElementById('w-email')?.value.trim() ?? '';

            if (isGuestMode()) {
                const pic = document.getElementById('w-pic-name')?.value.trim();
                const pass = document.getElementById('w-password')?.value ?? '';
                const passConfirm = document.getElementById('w-password-confirm')?.value ?? '';

                if (!pic) errors.push('Nama PIC / penanggung jawab wajib diisi.');
                if (!email.includes('@')) errors.push('Email login belum valid.');
                if (email.includes('@') && !emailAvailable) {
                    errors.push(emailStatus?.textContent?.replace(/\s+/g, ' ').trim() || 'Email tidak tersedia.');
                }
                if (!nama) errors.push('Nama madrasah wajib diisi.');
                if (pass.length < 8) errors.push(`Password minimal 8 karakter (saat ini ${pass.length} karakter).`);
                if (pass.length >= 8 && pass !== passConfirm) errors.push('Konfirmasi password tidak sama.');
            } else {
                if (!nama) errors.push('Nama madrasah wajib diisi.');
                if (!email.includes('@')) errors.push('Email belum valid.');
            }
        } else if (step === 4) {
            if (!document.querySelector('input[name="deploy_payment_method"]:checked')) {
                errors.push('Pilih metode pembayaran terlebih dahulu.');
            }
        }

        return errors;
    };

    const showStepHint = (errors) => {
        if (!stepHint) return;

        if (!errors.length) {
            stepHint.classList.add('hidden');
            stepHint.textContent = '';
            return;
        }

        // Tampilkan hanya di step data (2) agar tidak mengganggu step lain tanpa container
        if (step === 2) {
            stepHint.innerHTML = `<strong>Belum bisa lanjut:</strong> ${errors.join(' ')}`;
            stepHint.classList.remove('hidden');
        } else {
            stepHint.classList.add('hidden');
        }
    };

    const syncPasswordStatus = () => {
        if (!passwordStatus || !isGuestMode()) return;

        const pass = document.getElementById('w-password')?.value ?? '';
        const passConfirm = document.getElementById('w-password-confirm')?.value ?? '';

        if (!pass && !passConfirm) {
            passwordStatus.textContent = 'Password minimal 8 karakter.';
            passwordStatus.className = 'text-sm text-gray-400 -mt-1';
            return;
        }

        if (pass.length < 8) {
            passwordStatus.textContent = `Password masih ${pass.length} karakter — minimal 8.`;
            passwordStatus.className = 'text-sm text-rose-600 -mt-1';
            return;
        }

        if (pass !== passConfirm) {
            passwordStatus.textContent = 'Konfirmasi password belum sama.';
            passwordStatus.className = 'text-sm text-rose-600 -mt-1';
            return;
        }

        passwordStatus.textContent = 'Password siap digunakan.';
        passwordStatus.className = 'text-sm text-emerald-600 -mt-1';
    };

    const validateStep = () => {
        const errors = getStepErrors();
        // Tetap bisa diklik agar alert alasan tampil; tampilan dimuted jika invalid
        nextBtn.disabled = false;
        nextBtn.classList.toggle('opacity-50', errors.length > 0);
        nextBtn.classList.toggle('cursor-not-allowed', errors.length > 0);
        nextBtn.dataset.invalid = errors.length ? '1' : '0';
        nextBtn.title = errors.length ? errors.join(' ') : '';
        showStepHint(errors);
        if (step === 2) syncPasswordStatus();
    };

    const scrollToWizard = () => {
        if (wizard.getBoundingClientRect().top < 0) {
            wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const showPanel = (n) => {
        step = n;
        document.querySelectorAll('.wizard-panel').forEach((p) => {
            p.classList.toggle('hidden', Number(p.dataset.panel) !== n);
        });
        document.querySelectorAll('.wizard-step-dot').forEach((d) => {
            d.classList.toggle('bg-emerald-500', Number(d.dataset.step) <= n);
            d.classList.toggle('bg-emerald-100', Number(d.dataset.step) > n);
        });
        document.querySelectorAll('[data-step-label]').forEach((label) => {
            const reached = Number(label.dataset.stepLabel) <= n;
            label.classList.toggle('text-emerald-700', reached);
            label.classList.toggle('font-medium', reached);
            label.classList.toggle('text-gray-400', !reached);
        });
        prevBtn.classList.toggle('hidden', n === 1);
        if (n === 4) {
            nextBtn.textContent = 'Bayar & Deploy';
        } else if (n === 3) {
            nextBtn.textContent = isGuestMode() ? 'Buat Akun & Lanjut Bayar' : 'Lanjut ke Pembayaran';
        } else {
            nextBtn.textContent = 'Lanjut';
        }
        validateStep();
    };

    const syncDeployTotals = () => {
        const pkg = document.querySelector('input[name="package"]:checked')?.value;
        const packageAmount = packagePrices[pkg] || 0;
        const selected = document.querySelector('input[name="deploy_payment_method"]:checked');
        const adminFee = selected
            ? (selected.dataset.feeCustomer === '1'
                ? Math.ceil(packageAmount * Number(selected.dataset.feePercent || 0) / 100) + Number(selected.dataset.feeFlat || 0)
                : Number(selected.dataset.adminFee || 0))
            : 0;

        const hargaEl = document.getElementById('c-harga');
        const adminEl = document.getElementById('c-admin');
        const totalEl = document.getElementById('c-total');

        if (hargaEl) hargaEl.textContent = formatRp(packageAmount);
        if (adminEl) {
            adminEl.textContent = selected ? (adminFee > 0 ? formatRp(adminFee) : 'Gratis') : '—';
            adminEl.classList.toggle('text-gray-400', !selected);
        }
        if (totalEl) totalEl.textContent = formatRp(packageAmount + adminFee);
    };

    const checkEmail = async (value) => {
        if (!isGuestMode() || !emailStatus) return;

        if (!value || !value.includes('@')) {
            emailAvailable = false;
            emailStatus.className = 'text-sm mt-2 text-gray-400';
            emailStatus.textContent = 'Masukkan email valid.';
            emailStatus.classList.remove('hidden');
            validateStep();
            return;
        }

        emailStatus.className = 'text-sm mt-2 text-gray-400';
        emailStatus.textContent = 'Mengecek email...';
        emailStatus.classList.remove('hidden');

        try {
            const res = await fetch(`/api/email/check?email=${encodeURIComponent(value)}`);
            const data = await res.json();
            emailAvailable = data.available;
            emailStatus.className = `text-sm mt-2 ${data.available ? 'text-emerald-600' : 'text-rose-600'}`;
            if (data.available) {
                emailStatus.textContent = data.message;
            } else {
                emailStatus.innerHTML = `${data.message} <a href="${data.login_url}" class="underline font-medium">Login</a>`;
            }
        } catch {
            emailAvailable = false;
            emailStatus.className = 'text-sm mt-2 text-rose-600';
            emailStatus.textContent = 'Gagal mengecek email.';
        }
        validateStep();
    };

    const checkSubdomain = async (value) => {
        if (!value || value.length < 3) {
            subdomainAvailable = false;
            subdomainStatus.className = 'text-sm mt-2 text-gray-400';
            subdomainStatus.textContent = 'Minimal 3 karakter.';
            subdomainStatus.classList.remove('hidden');
            validateStep();
            return;
        }

        subdomainStatus.className = 'text-sm mt-2 text-gray-400';
        subdomainStatus.textContent = 'Mengecek ketersediaan...';
        subdomainStatus.classList.remove('hidden');

        try {
            const res = await fetch(`/api/subdomain/check?subdomain=${encodeURIComponent(value)}`);
            const data = await res.json();
            subdomainAvailable = data.available;
            subdomainStatus.className = `text-sm mt-2 ${data.available ? 'text-emerald-600' : 'text-rose-600'}`;
            subdomainStatus.textContent = data.message;
        } catch {
            subdomainAvailable = false;
            subdomainStatus.className = 'text-sm mt-2 text-rose-600';
            subdomainStatus.textContent = 'Gagal mengecek subdomain.';
        }
        validateStep();
    };

    const fillConfirm = () => {
        const sub = subdomainInput.value.trim();
        const pkg = document.querySelector('input[name="package"]:checked')?.value;
        document.getElementById('c-fqdn').textContent = `${sub}.rdm-online.id`;
        document.getElementById('c-nama').textContent = document.getElementById('w-nama').value.trim();
        document.getElementById('c-jenjang').textContent = document.getElementById('w-jenjang').value;
        document.getElementById('c-email').textContent = document.getElementById('w-email').value.trim();
        const picEl = document.getElementById('c-pic');
        if (picEl) {
            picEl.textContent = document.getElementById('w-pic-name')?.value.trim() ?? '';
        }
        const pkgCode = packageLabels[pkg] ? pkg : 'monthly';
        document.getElementById('c-paket').textContent = `${packageLabels[pkgCode]} (Rp${Number(packagePrices[pkgCode] || 0).toLocaleString('id-ID')})`;
        syncDeployTotals();
    };

    const showPrepareError = (message) => {
        const err = document.getElementById('deploy-prepare-error');
        if (err) {
            err.textContent = message;
            err.classList.remove('hidden');
        }
    };

    const clearPrepareError = () => {
        document.getElementById('deploy-prepare-error')?.classList.add('hidden');
    };

    const prepareDeploy = async () => {
        clearPrepareError();

        const payload = {
            subdomain: subdomainInput.value.trim(),
            madrasah_name: document.getElementById('w-nama').value.trim(),
            jenjang: document.getElementById('w-jenjang').value,
            package: document.querySelector('input[name="package"]:checked')?.value,
        };

        if (isGuestMode()) {
            payload.pic_name = document.getElementById('w-pic-name').value.trim();
            payload.email = document.getElementById('w-email').value.trim();
            payload.password = document.getElementById('w-password').value;
            payload.password_confirmation = document.getElementById('w-password-confirm').value;
            payload.referral_code = document.getElementById('w-referral')?.value.trim() || null;
        }

        nextBtn.disabled = true;
        nextBtn.textContent = isGuestMode() ? 'Membuat akun...' : 'Menyiapkan...';

        try {
            const res = await fetch('/api/deploy/prepare', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();
            if (!res.ok) {
                const msg = data.message
                    ?? (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                    ?? 'Gagal menyiapkan akun.';
                throw new Error(msg);
            }

            preparedInstanceId = data.instance_id;

            if (data.account_created) {
                wizard.dataset.guest = '0';
                emailAvailable = true;
                document.getElementById('deploy-account-hint')?.classList.add('hidden');
            }

            const notice = document.getElementById('deploy-prepare-notice');
            if (notice) {
                notice.textContent = data.account_created
                    ? 'Akun berhasil dibuat. Cek email untuk petunjuk login, lalu pilih metode pembayaran.'
                    : 'RDM disiapkan. Silakan pilih metode pembayaran di bawah.';
                notice.classList.remove('hidden');
            }

            return true;
        } catch (e) {
            showPrepareError(e.message);
            return false;
        } finally {
            nextBtn.disabled = false;
            validateStep();
        }
    };

    const applyResumeInstance = (resume) => {
        preparedInstanceId = resume.id;
        subdomainInput.value = resume.subdomain;
        subdomainAvailable = true;
        document.getElementById('w-nama').value = resume.madrasah_name ?? '';
        document.getElementById('w-jenjang').value = resume.jenjang ?? 'MI';

        const emailInput = document.getElementById('w-email');
        if (emailInput && resume.email) {
            emailInput.value = resume.email;
        }

        const pkgRadio = document.querySelector(`input[name="package"][value="${resume.package}"]`);
        if (pkgRadio) {
            pkgRadio.checked = true;
        }

        fillConfirm();

        const notice = document.getElementById('deploy-prepare-notice');
        if (notice) {
            notice.textContent = `Lanjutkan pembayaran untuk ${resume.fqdn}. Subdomain direservasi hingga batas waktu pembayaran.`;
            notice.classList.remove('hidden');
        }
    };

    const showInlinePayment = async (data) => {
        formView.classList.add('hidden');
        progressView.classList.add('hidden');
        qrisView?.classList.remove('hidden');
        scrollToWizard();

        const amountEl = document.getElementById('qris-amount');
        const statusEl = document.getElementById('qris-status');
        const titleEl = document.getElementById('inline-payment-title');
        const subtitleEl = document.getElementById('inline-payment-subtitle');
        const qrisPanel = document.getElementById('inline-payment-qris');
        const vaPanel = document.getElementById('inline-payment-va');
        const redirectPanel = document.getElementById('inline-payment-redirect');
        const canvas = document.getElementById('qris-canvas');

        if (amountEl) {
            amountEl.textContent = data.amount ? formatRp(data.amount) : '';
        }
        if (statusEl) {
            statusEl.textContent = 'Menunggu pembayaran...';
            statusEl.className = 'text-sm text-amber-700 font-medium';
        }

        const isVa = data.payment_mode === 'va_inline' || data.va_number;
        const isRedirect = data.payment_mode === 'redirect';

        redirectPanel?.classList.toggle('hidden', !isRedirect);

        if (isRedirect) {
            if (titleEl) titleEl.textContent = 'Menunggu Konfirmasi Pembayaran';
            if (subtitleEl) subtitleEl.textContent = data.payment_label ? `Metode: ${data.payment_label}` : 'Pembayaran sedang diverifikasi';
            qrisPanel?.classList.add('hidden');
            vaPanel?.classList.add('hidden');

            const link = document.getElementById('inline-payment-checkout-link');
            document.getElementById('inline-payment-checkout-wrap')?.classList.toggle('hidden', !data.checkout_url);
            if (link) link.href = data.checkout_url || '#';
        } else if (isVa) {
            if (titleEl) titleEl.textContent = 'Transfer Virtual Account';
            if (subtitleEl) subtitleEl.textContent = 'Transfer ke nomor VA di bawah — tidak perlu buka halaman lain';
            qrisPanel?.classList.add('hidden');
            vaPanel?.classList.remove('hidden');

            const bankEl = document.getElementById('va-bank-label');
            const numberEl = document.getElementById('va-number');
            if (bankEl) bankEl.textContent = data.bank_label || data.channel_code || 'Virtual Account';
            if (numberEl) numberEl.textContent = data.va_number || '';

            if (!data.va_number) {
                throw new Error('Nomor Virtual Account tidak tersedia dari server.');
            }
        } else {
            if (titleEl) titleEl.textContent = 'Bayar via QRIS';
            if (subtitleEl) subtitleEl.textContent = 'Scan QR di bawah — tidak perlu buka halaman lain';
            vaPanel?.classList.add('hidden');
            qrisPanel?.classList.remove('hidden');

            if (!canvas || !data.qr_string) {
                throw new Error('Kode QRIS tidak tersedia dari server.');
            }

            await QRCode.toCanvas(canvas, data.qr_string, {
                width: 260,
                margin: 2,
                errorCorrectionLevel: 'M',
            });
        }

        initIcons();
    };

    document.getElementById('copy-va-number')?.addEventListener('click', async () => {
        const number = document.getElementById('va-number')?.textContent?.trim();
        if (!number) return;

        const button = document.getElementById('copy-va-number');
        try {
            await navigator.clipboard.writeText(number);
            if (button) button.textContent = 'Tersalin!';
        } catch {
            window.prompt('Salin nomor VA:', number);
        }
    });

    const startDeployPoll = (instanceId) => {
        if (pollState.timer) clearInterval(pollState.timer);
        pollState = startDeployStatusPoll(progressView, `/api/deploy/${instanceId}`, pollState);
    };

    const showDeployProgress = (instanceId) => {
        if (paymentPollTimer) clearInterval(paymentPollTimer);
        formView.classList.add('hidden');
        qrisView?.classList.add('hidden');
        progressView.classList.remove('hidden');
        resetDeployProgressUI(progressView);
        scrollToWizard();
        startDeployPoll(instanceId);
    };

    const fetchDeployPayment = async (externalId) => {
        const res = await fetch(`/api/deploy/payment/${encodeURIComponent(externalId)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        return res.ok ? res.json() : null;
    };

    const waitForDeployPayment = (externalId, instanceId) => {
        if (paymentPollTimer) clearInterval(paymentPollTimer);

        paymentPollTimer = setInterval(async () => {
            try {
                const data = await fetchDeployPayment(externalId);
                const statusEl = document.getElementById('qris-status');

                if (data?.status === 'paid') {
                    showDeployProgress(data.instance_id ?? instanceId);
                } else if (data && ['expired', 'failed'].includes(data.status)) {
                    clearInterval(paymentPollTimer);
                    if (statusEl) {
                        statusEl.textContent = data.status === 'expired'
                            ? 'Batas waktu pembayaran habis. Silakan ganti metode pembayaran.'
                            : 'Pembayaran gagal. Silakan ganti metode pembayaran.';
                        statusEl.className = 'text-sm text-rose-700 font-medium';
                    }
                }
            } catch {
                // retry on next interval
            }
        }, 3000);
    };

    const resumePaidCheckout = async (externalId) => {
        try {
            const data = await fetchDeployPayment(externalId);

            if (!data) {
                showPanel(1);
                return;
            }

            preparedInstanceId = data.instance_id;

            if (data.status === 'paid') {
                showDeployProgress(data.instance_id);
                return;
            }

            await showInlinePayment({ ...data, payment_mode: 'redirect' });

            if (data.status === 'pending') {
                waitForDeployPayment(externalId, data.instance_id);
            } else {
                const statusEl = document.getElementById('qris-status');
                if (statusEl) {
                    statusEl.textContent = 'Tagihan ini sudah tidak berlaku. Silakan ganti metode pembayaran.';
                    statusEl.className = 'text-sm text-rose-700 font-medium';
                }
            }
        } catch {
            showPanel(1);
        }
    };

    document.getElementById('change-payment-method')?.addEventListener('click', () => {
        if (paymentPollTimer) clearInterval(paymentPollTimer);
        window.location.href = preparedInstanceId
            ? `${window.location.pathname}?resume=${encodeURIComponent(preparedInstanceId)}`
            : window.location.pathname;
    });

    const payAndDeploy = async () => {
        const paymentMethod = document.querySelector('input[name="deploy_payment_method"]:checked')?.value;
        if (!paymentMethod) return;

        if (!preparedInstanceId) {
            const err = document.getElementById('deploy-payment-error');
            if (err) {
                err.textContent = 'Data deploy belum disiapkan. Kembali ke langkah paket dan klik Lanjut.';
                err.classList.remove('hidden');
            }
            return;
        }

        nextBtn.disabled = true;
        nextBtn.textContent = 'Memproses...';

        const payload = {
            instance_id: preparedInstanceId,
            payment_method: paymentMethod,
        };

        try {
            const res = await fetch('/api/deploy/checkout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();
            if (!res.ok) {
                const msg = data.message
                    ?? (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                    ?? 'Gagal membuat pembayaran.';
                throw new Error(msg);
            }

            nextBtn.textContent = 'Menunggu pembayaran...';

            const isQris = paymentMethod === 'qris' || data.payment_mode === 'qris_inline';
            const isVa = vaMethods.includes(paymentMethod) || data.payment_mode === 'va_inline';

            if (isQris) {
                if (!data.qr_string) {
                    throw new Error(data.message || 'Gagal membuat kode QRIS. Muat ulang halaman (Ctrl+Shift+R) lalu coba lagi.');
                }

                await showInlinePayment(data);
                waitForDeployPayment(data.external_id, data.instance_id);
                return;
            }

            if (isVa) {
                if (!data.va_number) {
                    throw new Error(data.message || 'Gagal membuat Virtual Account. Muat ulang halaman lalu coba lagi.');
                }

                await showInlinePayment(data);
                waitForDeployPayment(data.external_id, data.instance_id);
                return;
            }

            if (!data.invoice_url) {
                throw new Error(data.message || 'URL pembayaran tidak tersedia. Muat ulang halaman lalu coba lagi.');
            }

            nextBtn.textContent = 'Membuka halaman pembayaran...';
            window.location.href = data.invoice_url;
        } catch (e) {
            nextBtn.disabled = false;
            nextBtn.textContent = 'Bayar & Deploy';
            const err = document.getElementById('deploy-payment-error');
            if (err) {
                err.textContent = e.message;
                err.classList.remove('hidden');
            }
        }
    };

    const startDeploy = async () => {
        await payAndDeploy();
    };

    document.querySelectorAll('input[name="deploy_payment_method"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            syncDeployTotals();
            validateStep();
            const hint = document.getElementById('deploy-payment-hint');
            if (hint) {
                if (radio.value === 'qris') {
                    hint.textContent = 'QRIS ditampilkan langsung setelah klik Bayar & Deploy';
                } else if (vaMethods.includes(radio.value)) {
                    hint.textContent = 'Nomor Virtual Account ditampilkan langsung setelah klik Bayar & Deploy';
                } else {
                    hint.textContent = 'Setelah klik Bayar & Deploy, Anda diarahkan ke halaman pembayaran. Halaman ini kembali otomatis setelah bayar.';
                }
            }
        });
    });

    subdomainInput.addEventListener('input', () => {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(() => checkSubdomain(subdomainInput.value.trim()), 500);
    });

    ['w-nama', 'w-email', 'w-pic-name', 'w-password', 'w-password-confirm'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', validateStep);
    });

    document.getElementById('w-email')?.addEventListener('input', () => {
        if (!isGuestMode()) return;
        clearTimeout(emailCheckTimer);
        emailCheckTimer = setTimeout(() => checkEmail(document.getElementById('w-email').value.trim()), 500);
    });

    document.querySelectorAll('input[name="package"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (step === 4) syncDeployTotals();
        });
    });

    prevBtn.addEventListener('click', () => {
        showPanel(step - 1);
        scrollToWizard();
    });

    nextBtn.addEventListener('click', async () => {
        const errors = getStepErrors();
        if (errors.length) {
            alert(`Belum bisa lanjut:\n\n• ${errors.join('\n• ')}`);
            showStepHint(errors);
            return;
        }

        if (step < 4) {
            if (step === 3) {
                fillConfirm();
                const prepared = await prepareDeploy();
                if (!prepared) {
                    return;
                }
            }
            showPanel(step + 1);
            scrollToWizard();
        } else {
            startDeploy();
        }
    });

    initPasswordToggles(wizard);
    initIcons();

    const params = new URLSearchParams(window.location.search);

    if (window.__rdmResumeInstance) {
        applyResumeInstance(window.__rdmResumeInstance);
        showPanel(4);
    } else if (params.get('monitor')) {
        showDeployProgress(params.get('monitor'));
    } else if (params.get('paid')) {
        resumePaidCheckout(params.get('paid'));
    } else {
        [['package', params.get('package')], ['deploy_payment_method', params.get('method')]].forEach(([name, value]) => {
            const input = value && wizard.querySelector(`input[name="${name}"][value="${CSS.escape(value)}"]`);
            if (input) {
                input.checked = true;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        showPanel(1);
    }
}

function initLandingMenu() {
    const toggle = document.getElementById('landing-menu-toggle');
    const menu = document.getElementById('landing-mobile-menu');

    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => menu.classList.toggle('hidden'));
    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => menu.classList.add('hidden'));
    });
}

function initPromoCountdowns() {
    document.querySelectorAll('[data-promo-countdown]').forEach((el) => {
        const endsAt = Date.parse(el.dataset.endsAt || '');
        if (! endsAt) {
            return;
        }

        const tick = () => {
            const diff = Math.max(0, endsAt - Date.now());
            const totalSec = Math.floor(diff / 1000);
            const d = Math.floor(totalSec / 86400);
            const h = Math.floor((totalSec % 86400) / 3600);
            const m = Math.floor((totalSec % 3600) / 60);
            const s = totalSec % 60;
            const pad = (n) => String(n).padStart(2, '0');

            el.querySelector('[data-cd="d"]') && (el.querySelector('[data-cd="d"]').textContent = pad(d));
            el.querySelector('[data-cd="h"]') && (el.querySelector('[data-cd="h"]').textContent = pad(h));
            el.querySelector('[data-cd="m"]') && (el.querySelector('[data-cd="m"]').textContent = pad(m));
            el.querySelector('[data-cd="s"]') && (el.querySelector('[data-cd="s"]').textContent = pad(s));

            if (diff <= 0) {
                el.classList.add('opacity-60');
                const label = el.querySelector('.opacity-90, p');
                if (label && label.textContent.includes('berakhir')) {
                    label.textContent = 'Promo telah berakhir';
                }
            }
        };

        tick();
        setInterval(tick, 1000);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initIcons();
    initPasswordToggles();
    initSidebar();
    initDeployWizard();
    initLandingMenu();
    initPromoCountdowns();
});
