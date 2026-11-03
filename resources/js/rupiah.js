// Display Indonesian currency while submitting decimal numeric strings.
// String grouping avoids rounding large amounts through Number during typing.
export function parseRupiah(value) {
    const text = String(value ?? '').trim();
    const negative = /^(?:Rp\.?\s*)?-/i.test(text);
    const cleaned = text.replace(/[^\d,]/g, '');
    const [integer = '', ...fractions] = cleaned.split(',');
    if (!integer && !fractions.join('')) return '';
    const whole = (integer || '0').replace(/^0+(?=\d)/, '');
    return `${negative ? '-' : ''}${whole}${fractions.length ? `.${fractions.join('').slice(0, 2)}` : ''}`;
}

export function formatRupiah(value) {
    if (value === '' || value === null || value === undefined) return '';
    const [integer, fraction] = String(value).split('.');
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return `Rp ${grouped}${fraction !== undefined ? `,${fraction}` : ''}`;
}

export function rupiahNumber(input) {
    return Number(parseRupiah(input.value)) || 0;
}

const initialized = new WeakSet();
const registeredForms = new WeakSet();

export function setupRupiahInput(input) {
    if (initialized.has(input)) return;
    initialized.add(input);
    // Server-rendered values use decimal dots, before any display formatting.
    const initial = input.value.trim();
    input.type = 'text';
    input.inputMode = input.dataset.rupiahDecimals === '2' ? 'decimal' : 'numeric';
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.placeholder = input.placeholder.startsWith('Rp') ? input.placeholder : 'Rp 0';
    input.value = formatRupiah(initial === '' ? '' : initial);

    const validate = () => {
        const raw = parseRupiah(input.value);
        const numeric = Number(raw);
        let message = '';
        if (raw !== '') {
            if (!Number.isFinite(numeric)) message = 'Nominal tidak valid.';
            else if (input.dataset.rupiahDecimals !== '2' && !Number.isInteger(numeric)) message = 'Gunakan nominal rupiah bulat tanpa desimal.';
            else if (input.hasAttribute('min') && numeric < Number(input.getAttribute('min'))) message = `Nominal minimal ${formatRupiah(input.getAttribute('min'))}.`;
            else if (input.hasAttribute('max') && numeric > Number(input.getAttribute('max'))) message = `Nominal maksimal ${formatRupiah(input.getAttribute('max'))}.`;
        }
        input.setCustomValidity(message);
    };

    input.addEventListener('input', () => {
        const cursor = input.selectionStart ?? input.value.length;
        const significant = input.value.slice(0, cursor).replace(/[^\d,]/g, '').length;
        input.value = formatRupiah(parseRupiah(input.value));
        let position = input.value ? 3 : 0;
        let seen = 0;
        for (let i = 3; i < input.value.length && seen < significant; i++) {
            if (/[\d,]/.test(input.value[i])) seen++;
            position = i + 1;
        }
        input.setSelectionRange(position, position);
        validate();
    });
    input.addEventListener('blur', () => {
        const raw = parseRupiah(input.value).replace(/\.$/, '');
        input.value = formatRupiah(raw);
        validate();
    });
    validate();

    const form = input.form;
    if (form && !registeredForms.has(form)) {
        registeredForms.add(form);
        // Native submit and new FormData(form) both fire formdata. Keep the
        // display formatted even when submission is canceled or retried.
        form.addEventListener('formdata', (event) => {
            form.querySelectorAll('[data-rupiah]').forEach((field) => {
                if (field.name && !field.matches(':disabled')) {
                    event.formData.set(field.name, parseRupiah(field.value).replace(/\.$/, ''));
                }
            });
        });
        form.addEventListener('reset', () => {
            setTimeout(() => {
                form.querySelectorAll('[data-rupiah]').forEach((field) => {
                    field.value = formatRupiah(field.defaultValue);
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }, 0);
        });
    }
}

export function setupRupiahInputs(root = document) {
    root.querySelectorAll('[data-rupiah]').forEach(setupRupiahInput);
}
