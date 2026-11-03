import { setupWebPush } from './web-push.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import * as bootstrap from 'bootstrap';
import '../css/app.css';
import { setupRupiahInputs, rupiahNumber } from './rupiah.js';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    setupRupiahInputs();
    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (! window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        window.setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert).close(), 5000);
    });

    const componentsContainer = document.querySelector('[data-components]');
    const componentTemplate = document.querySelector('#component-template');
    const addComponentButton = document.querySelector('[data-add-component]');

    if (componentsContainer && componentTemplate && addComponentButton) {
        let nextIndex = 0;

        const updateTotal = () => {
            const total = [...componentsContainer.querySelectorAll('[data-field="amount"]')]
                .reduce((sum, input) => sum + rupiahNumber(input), 0);
            document.querySelector('[data-invoice-total]').textContent = `Rp ${total.toLocaleString('id-ID')}`;
        };

        const addComponent = () => {
            const fragment = componentTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.component-row');
            row.querySelector('[data-field="name"]').name = `components[${nextIndex}][name]`;
            row.querySelector('[data-field="amount"]').name = `components[${nextIndex}][amount]`;
            nextIndex += 1;

            componentsContainer.appendChild(fragment);
            setupRupiahInputs(row);
            row.querySelector('[data-field="amount"]').addEventListener('input', updateTotal);
            row.querySelector('[data-remove-component]').addEventListener('click', () => {
                if (componentsContainer.children.length > 1) {
                    row.remove();
                    updateTotal();
                }
            });
        };

        addComponentButton.addEventListener('click', addComponent);
        addComponent();
    }

    const recipientModes = document.querySelectorAll('[data-recipient-mode]');
    const recipientList = document.querySelector('[data-recipient-list]');
    const residentCheckboxes = document.querySelectorAll('[data-resident-checkbox]');
    const selectAllResidents = document.querySelector('[data-select-all]');

    if (recipientModes.length && recipientList) {
        const updateRecipientMode = () => {
            const selectedMode = document.querySelector('[data-recipient-mode]:checked')?.value === 'selected';
            recipientList.classList.toggle('opacity-50', !selectedMode);
            residentCheckboxes.forEach((checkbox) => {
                checkbox.disabled = !selectedMode;
            });
            selectAllResidents.disabled = !selectedMode;
        };

        recipientModes.forEach((radio) => radio.addEventListener('change', updateRecipientMode));
        selectAllResidents.addEventListener('change', () => {
            residentCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAllResidents.checked;
            });
        });
        residentCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', () => {
            selectAllResidents.checked = [...residentCheckboxes].every((item) => item.checked);
        }));
        updateRecipientMode();
    }
});

// One request at a time, only while this page is visible. Database notifications
// do not require a WebSocket server or queue worker.
document.addEventListener('DOMContentLoaded', () => {
    const widget = document.querySelector('[data-notification-widget]');
    if (!widget) return;
    const badge = widget.querySelector('[data-notification-badge]');
    const toggle = widget.querySelector('[data-notification-toggle]');
    const items = widget.querySelector('[data-notification-items]');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let busy = false;
    let stopped = false;
    let listNeeded = false;

    const showCount = (count) => {
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.classList.toggle('d-none', count === 0);
        toggle.setAttribute('aria-label', `Buka notifikasi, ${count} belum dibaca`);
    };
    const request = async (url, method = 'GET') => {
        const response = await fetch(url, {
            method, credentials: 'same-origin', redirect: 'error',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
            signal: AbortSignal.timeout(15000),
        });
        if ([401, 403, 419].includes(response.status)) stopped = true;
        if (!response.ok) throw new Error('Notifikasi belum dapat dimuat.');
        return response.json();
    };
    const renderItems = (notifications) => {
        items.replaceChildren();
        if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'text-secondary p-3 mb-0';
            empty.textContent = 'Belum ada notifikasi.';
            items.append(empty);
        }
        notifications.forEach((notification) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `notification-item ${notification.read ? 'bg-white' : 'notification-unread'}`;
            const title = document.createElement('strong');
            title.textContent = notification.title;
            const message = document.createElement('span');
            message.className = 'small my-1';
            message.textContent = notification.message;
            const time = document.createElement('small');
            time.className = 'text-secondary';
            time.textContent = notification.time;
            button.append(title, message, time);
            button.addEventListener('click', async () => {
                if (busy || stopped) return;
                busy = true;
                button.disabled = true;
                try {
                    const data = await request(notification.read_url, 'PATCH');
                    window.location.assign(data.url);
                } catch {
                    button.disabled = false;
                    time.textContent = 'Gagal membuka. Silakan coba lagi.';
                } finally {
                    busy = false;
                }
            });
            items.append(button);
        });
    };
    const refresh = async (withList = false) => {
        if (withList) listNeeded = true;
        if (busy || stopped || document.hidden) return;
        busy = true;
        const loadList = listNeeded;
        listNeeded = false;
        try {
            const data = await request(loadList ? widget.dataset.listUrl : widget.dataset.countUrl);
            showCount(data.unread_count);
            if (loadList) renderItems(data.items);
        } catch {
            if (loadList) items.textContent = 'Gagal memuat notifikasi. Tutup dan buka kembali untuk mencoba lagi.';
        } finally {
            busy = false;
            if (listNeeded && !stopped) refresh();
        }
    };
    toggle.addEventListener('shown.bs.dropdown', () => refresh(true));
    widget.querySelector('[data-notification-read-all]').addEventListener('click', async (event) => {
        if (busy || stopped) return;
        busy = true;
        const button = event.currentTarget;
        button.disabled = true;
        try {
            await request(widget.dataset.readAllUrl, 'PATCH');
            showCount(0);
            listNeeded = true;
        } catch {
            items.textContent = 'Gagal menandai notifikasi. Silakan coba lagi.';
        } finally {
            busy = false;
            button.disabled = false;
            if (listNeeded) refresh();
        }
    });
    const poll = () => refresh(toggle.getAttribute('aria-expanded') === 'true');
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    window.setInterval(poll, 30000);
    refresh();
});


document.addEventListener('DOMContentLoaded', () => {
    const occupancy = document.querySelector('[data-house-occupancy]');
    const billing = document.querySelector('[data-vacant-billing]');
    const amount = document.querySelector('[data-vacant-amount]');
    if (occupancy && billing && amount) {
        const status = document.querySelector('[data-vacant-rule-status]');
        const help = document.querySelector('[data-vacant-rule-help]');
        const update = () => {
            const vacant = occupancy.value === 'vacant';
            const custom = vacant && billing.value === 'custom';
            billing.disabled = !vacant;
            billing.required = vacant;
            amount.disabled = !custom;
            amount.required = custom;
            status.textContent = vacant ? 'Aktif untuk tagihan IPL baru' : 'Tidak aktif pada kondisi ini';
            status.classList.toggle('text-bg-success', vacant);
            status.classList.toggle('text-bg-secondary', !vacant);
            help.textContent = vacant
                ? 'Pilih tarif penuh, nominal khusus, atau bebas iuran untuk rumah kosong ini.'
                : 'Rumah dihuni, dikontrakkan, dan renovasi memakai komponen normal dari formulir Buat Tagihan. Pengaturan rumah kosong sebelumnya tetap tersimpan dan tidak digunakan.';
        };
        occupancy.addEventListener('change', update);
        billing.addEventListener('change', update);
        update();
    }
    const house = document.querySelector('[data-resident-house]');
    const address = document.querySelector('[data-resident-address]');
    if (house && address) {
        const update = () => {
            if (house.value) address.value = house.selectedOptions[0].dataset.address;
            address.readOnly = Boolean(house.value);
        };
        house.addEventListener('change', update);
        update();
    }
});

document.addEventListener('DOMContentLoaded', () => { setupWebPush(); });

document.addEventListener('DOMContentLoaded', () => {
    // Keep column context when tables become stacked cards on small screens.
    document.querySelectorAll('table.table-mobile-cards').forEach((table) => {
        const labels = [...(table.tHead?.rows[0]?.cells || [])].map((cell) => cell.textContent.trim());
        [...(table.tBodies[0]?.rows || [])].forEach((row) => {
            [...row.cells].forEach((cell, index) => {
                if (cell.colSpan === 1 && labels[index]) cell.dataset.label = labels[index];
            });
        });
    });
});
