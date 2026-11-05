import test from 'node:test';
import assert from 'node:assert/strict';
import { paymentSelectionTotal, setupPaymentSelections } from '../../resources/js/payment-selection.js';

test('selection totals keep exact cents and large values without floating point rounding', () => {
    assert.deepEqual(paymentSelectionTotal([]), { cents: 0n, formatted: 'Rp 0' });
    assert.deepEqual(paymentSelectionTotal(['125000.25', '150000.75']), { cents: 27500100n, formatted: 'Rp 275.001' });
    assert.equal(paymentSelectionTotal(['0.10', '0.20']).formatted, 'Rp 0,30');
    assert.equal(paymentSelectionTotal(['9999999999999.99']).cents, 999999999999999n);
    assert.throws(() => paymentSelectionTotal(['-150000']));
    assert.throws(() => paymentSelectionTotal(['Rp 150.000']));
});

test('select all, deselect and large totals update the payment action and summary', () => {
    class Field extends EventTarget { checked = false; disabled = false; textContent = ''; indeterminate = false; }
    const inputs = ['125000.25', '150000.75'].map((amount) => Object.assign(new Field(), { dataset: { amount } }));
    const all = new Field(); const count = new Field(); const total = new Field(); const next = new Field();
    const elements = { '[data-payment-all]': all, '[data-payment-count]': count, '[data-payment-total]': total, '[data-payment-continue]': next };
    const form = Object.assign(new EventTarget(), { querySelectorAll: () => inputs, querySelector: (selector) => elements[selector] });
    setupPaymentSelections({ querySelectorAll: () => [form] });
    assert.equal(next.disabled, true);
    all.checked = true; all.dispatchEvent(new Event('change'));
    assert.equal(count.textContent, '2'); assert.equal(total.textContent, 'Rp 275.001'); assert.equal(next.disabled, false);
    inputs[0].checked = false; inputs[0].dispatchEvent(new Event('change'));
    assert.equal(all.indeterminate, true); assert.equal(total.textContent, 'Rp 150.000,75');
    inputs[0].dataset.amount = '9999999999999.99'; all.checked = true; all.dispatchEvent(new Event('change'));
    assert.equal(next.disabled, true);
    all.checked = false; all.dispatchEvent(new Event('change'));
    const submit = new Event('submit', { cancelable: true }); form.dispatchEvent(submit);
    assert.equal(submit.defaultPrevented, true); assert.equal(count.textContent, '0');
});
