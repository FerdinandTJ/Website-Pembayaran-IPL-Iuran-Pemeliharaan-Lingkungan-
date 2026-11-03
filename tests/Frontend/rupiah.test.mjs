import test from 'node:test';
import assert from 'node:assert/strict';
import { formatRupiah, parseRupiah, rupiahNumber, setupRupiahInput } from '../../resources/js/rupiah.js';

class Form extends EventTarget {
    fields = [];
    querySelectorAll() { return this.fields; }
    submitData() {
        const data = new FormData();
        this.fields.filter((field) => !field.disabled).forEach((field) => data.set(field.name, field.value));
        const event = new Event('formdata');
        event.formData = data;
        this.dispatchEvent(event);
        return data;
    }
}
class Input extends EventTarget {
    constructor(form, name, value = '', decimals = false) {
        super();
        Object.assign(this, { form, name, value, defaultValue: value, placeholder: '', dataset: decimals ? { rupiahDecimals: '2' } : {}, disabled: false, selectionStart: value.length, attributes: { min: '1' }, validationMessage: '' });
        form.fields.push(this);
    }
    getAttribute(name) { return this.attributes[name]; }
    hasAttribute(name) { return name in this.attributes; }
    matches() { return this.disabled; }
    setCustomValidity(message) { this.validationMessage = message; }
    setSelectionRange(position) { this.selectionStart = position; }
    typeValue(value, cursor = value.length) {
        this.value = value;
        this.selectionStart = cursor;
        this.dispatchEvent(new Event('input'));
    }
}

test('formats typed amounts and preserves empty, zero, decimals, and large numeric strings', () => {
    assert.equal(formatRupiah('125000'), 'Rp 125.000');
    assert.equal(formatRupiah('125000.50'), 'Rp 125.000,50');
    assert.equal(formatRupiah('999999999999999999'), 'Rp 999.999.999.999.999.999');
    assert.equal(formatRupiah(''), '');
    assert.equal(formatRupiah('0'), 'Rp 0');
});
test('pasted Indonesian currency becomes numeric request data without silent negative conversion', () => {
    assert.equal(parseRupiah('Rp 1.250.000'), '1250000');
    assert.equal(parseRupiah('Rp. 1.250.000,75'), '1250000.75');
    assert.equal(parseRupiah('00025000'), '25000');
    assert.equal(parseRupiah('Rp -5.000'), '-5000');
    assert.equal(parseRupiah(''), '');
});
test('initial values render as rupiah, edits update totals, and submit sends only raw numbers', () => {
    const form = new Form();
    const first = new Input(form, 'components[0][amount]', '50000');
    const second = new Input(form, 'components[1][amount]');
    setupRupiahInput(first);
    setupRupiahInput(second);
    assert.equal(first.value, 'Rp 50.000');
    second.typeValue('125000');
    assert.equal(second.value, 'Rp 125.000');
    assert.equal(rupiahNumber(first) + rupiahNumber(second), 175000);
    const data = form.submitData();
    assert.equal(data.get(first.name), '50000');
    assert.equal(data.get(second.name), '125000');
    assert.equal(second.value, 'Rp 125.000');
    assert.equal(form.submitData().get(second.name), '125000');
});
test('disabled vacancy amounts are omitted and can be enabled again without losing values', () => {
    const form = new Form();
    const field = new Input(form, 'vacant_amount', '25000');
    setupRupiahInput(field);
    field.disabled = true;
    assert.equal(form.submitData().has('vacant_amount'), false);
    field.disabled = false;
    assert.equal(form.submitData().get('vacant_amount'), '25000');
});
test('cashflow decimals survive formatting and integer-only monetary fields reject fractions', () => {
    const form = new Form();
    const cash = new Input(form, 'total_cashflow', '1234.5', true);
    const integer = new Input(form, 'vacant_amount', '25000');
    setupRupiahInput(cash);
    setupRupiahInput(integer);
    assert.equal(cash.value, 'Rp 1.234,5');
    assert.equal(form.submitData().get('total_cashflow'), '1234.5');
    integer.typeValue('25.000,50');
    assert.match(integer.validationMessage, /bulat/);
    cash.typeValue('25.000,50');
    assert.equal(cash.validationMessage, '');
    assert.equal(form.submitData().get('total_cashflow'), '25000.50');
});
test('emptying, range validation, middle edits, and repeated initialization stay usable', () => {
    const form = new Form();
    const field = new Input(form, 'amount', '1000');
    field.attributes.max = '100000';
    setupRupiahInput(field);
    setupRupiahInput(field);
    assert.equal(field.value, 'Rp 1.000');
    field.typeValue('Rp 12.500', 5);
    assert.equal(field.selectionStart, 5);
    field.typeValue('-5000');
    assert.match(field.validationMessage, /minimal/);
    field.typeValue('100001');
    assert.match(field.validationMessage, /maksimal/);
    field.typeValue('');
    assert.equal(field.value, '');
    assert.equal(form.submitData().get('amount'), '');
    field.typeValue('5000');
    assert.equal(field.validationMessage, '');
    assert.equal(field.value, 'Rp 5.000');
});
