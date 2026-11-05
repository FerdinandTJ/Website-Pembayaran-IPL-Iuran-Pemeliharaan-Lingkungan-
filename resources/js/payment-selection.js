export function paymentSelectionTotal(amounts) {
    let cents = 0n;
    for (const amount of amounts) {
        if (!/^\d{1,13}(?:\.\d{1,2})?$/.test(String(amount))) throw new Error('Nominal tidak valid');
        const [whole, fraction = ''] = String(amount).split('.');
        cents += BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
    }
    return { cents, formatted: `Rp ${(cents / 100n).toLocaleString('id-ID')}${cents % 100n ? `,${String(cents % 100n).padStart(2, '0')}` : ''}` };
}

export function setupPaymentSelections(root = document) {
    root.querySelectorAll('[data-payment-selection]').forEach((form) => {
        const inputs = [...form.querySelectorAll('[data-payment-invoice]')];
        const all = form.querySelector('[data-payment-all]');
        const update = () => {
            const selected = inputs.filter((input) => input.checked);
            const total = paymentSelectionTotal(selected.map((input) => input.dataset.amount));
            form.querySelector('[data-payment-count]').textContent = String(selected.length);
            form.querySelector('[data-payment-total]').textContent = total.formatted;
            form.querySelector('[data-payment-continue]').disabled = selected.length === 0 || selected.length > 100 || total.cents > 999999999999999n;
            if (all) {
                all.checked = inputs.length > 0 && selected.length === inputs.length;
                all.indeterminate = selected.length > 0 && selected.length < inputs.length;
            }
        };
        inputs.forEach((input) => input.addEventListener('change', update));
        all?.addEventListener('change', () => {
            inputs.forEach((input) => { input.checked = all.checked; });
            update();
        });
        form.addEventListener('submit', (event) => {
            if (!inputs.some((input) => input.checked)) event.preventDefault();
        });
        update();
    });
}
