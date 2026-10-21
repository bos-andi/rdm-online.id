<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('checkout-form');
    const payButton = document.getElementById('pay-button');
    const adminFeeEl = document.getElementById('summary-admin-fee');
    const totalEl = document.getElementById('summary-total');

    if (!form || !payButton) {
        return;
    }

    const packageAmount = Number(form.dataset.packageAmount || 0);
    const formatRp = (value) => 'Rp' + value.toLocaleString('id-ID');

    const radios = form.querySelectorAll('input[name="payment_method"]');

    const sync = () => {
        const selected = form.querySelector('input[name="payment_method"]:checked');
        payButton.disabled = !selected;

        if (!selected) {
            adminFeeEl.textContent = '—';
            adminFeeEl.classList.add('text-gray-400');
            adminFeeEl.classList.remove('text-gray-800');
            totalEl.textContent = formatRp(packageAmount);
            return;
        }

        const adminFee = selected.dataset.feeCustomer === '1'
            ? Math.ceil(packageAmount * Number(selected.dataset.feePercent || 0) / 100) + Number(selected.dataset.feeFlat || 0)
            : Number(selected.dataset.adminFee || 0);
        adminFeeEl.textContent = formatRp(adminFee);
        adminFeeEl.classList.remove('text-gray-400');
        adminFeeEl.classList.add('text-gray-800');
        totalEl.textContent = formatRp(packageAmount + adminFee);
    };

    radios.forEach((radio) => radio.addEventListener('change', sync));
    sync();

    form.addEventListener('submit', () => {
        payButton.disabled = true;
        payButton.textContent = 'Memproses...';
    });
});
</script>
