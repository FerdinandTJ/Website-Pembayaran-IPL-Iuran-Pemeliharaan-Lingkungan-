import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import * as bootstrap from 'bootstrap';
import '../css/app.css';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
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
                .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            document.querySelector('[data-invoice-total]').textContent = `Rp ${total.toLocaleString('id-ID')}`;
        };

        const addComponent = () => {
            const fragment = componentTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.component-row');
            row.querySelector('[data-field="name"]').name = `components[${nextIndex}][name]`;
            row.querySelector('[data-field="amount"]').name = `components[${nextIndex}][amount]`;
            nextIndex += 1;

            row.querySelector('[data-field="amount"]').addEventListener('input', updateTotal);
            row.querySelector('[data-remove-component]').addEventListener('click', () => {
                if (componentsContainer.children.length > 1) {
                    row.remove();
                    updateTotal();
                }
            });
            componentsContainer.appendChild(fragment);
        };

        addComponentButton.addEventListener('click', addComponent);
        addComponent();
    }
});
