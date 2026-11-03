export function decodeApplicationKey(value) {
    const padded = value.padEnd(Math.ceil(value.length / 4) * 4, '=').replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (letter) => letter.charCodeAt(0));
}

export function matchesApplicationKey(subscription, key) {
    const actual = subscription?.options?.applicationServerKey;
    if (!actual) return true;
    const expected = decodeApplicationKey(key);
    const bytes = new Uint8Array(actual);
    return bytes.length === expected.length && bytes.every((byte, i) => byte === expected[i]);
}

export async function setupWebPush() {
    const panel = document.querySelector('[data-web-push]');
    if (!panel) return;
    const status = panel.querySelector('[data-push-status]');
    const enable = panel.querySelector('[data-push-enable]');
    const disable = panel.querySelector('[data-push-disable]');
    const test = panel.querySelector('[data-push-test]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let config;
    let subscription;
    let busy = false;
    const message = (text) => { status.textContent = text; };
    const setActive = (active) => {
        enable.classList.toggle('d-none', active);
        disable.classList.toggle('d-none', !active);
        test.classList.toggle('d-none', !active);
        enable.disabled = busy;
        disable.disabled = busy;
        test.disabled = busy;
    };
    const request = async (url, method = 'GET', body) => {
        const response = await fetch(url, {
            method, credentials: 'same-origin', redirect: 'error',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined,
            signal: AbortSignal.timeout(20000),
        });
        if (!response.ok) {
            const error = new Error([401, 419].includes(response.status) ? 'Sesi berakhir. Masuk kembali untuk mengatur notifikasi.' : 'Pengaturan belum berhasil disimpan. Silakan coba lagi.');
            error.status = response.status;
            throw error;
        }
        return response.json();
    };
    if (!window.isSecureContext) {
        message('Notifikasi perangkat memerlukan HTTPS. Alamat HTTP dari IP lokal di HP tidak mendukungnya.');
        return;
    }
    const ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (ios && !(window.matchMedia('(display-mode: standalone)').matches || navigator.standalone)) {
        message('Di iPhone/iPad, tambahkan aplikasi ke Home Screen melalui menu Bagikan Safari, lalu buka dari ikon tersebut.');
        return;
    }
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        message('Browser ini belum mendukung Web Push. Gunakan browser lain yang mendukung.');
        return;
    }
    try {
        config = await request(panel.dataset.configUrl);
        if (!config.configured) { message('Notifikasi perangkat belum diaktifkan pada server. Hubungi pengurus.'); return; }
        const registration = await navigator.serviceWorker.register(panel.dataset.workerUrl, { scope: new URL('.', panel.dataset.workerUrl).pathname });
        await navigator.serviceWorker.ready;
        subscription = await registration.pushManager.getSubscription();
        if (Notification.permission === 'denied') {
            message('Izin diblokir. Ubah izin notifikasi situs pada pengaturan browser, kemudian muat ulang halaman ini.');
            return;
        }
        let active = false;
        if (subscription && Notification.permission === 'granted' && matchesApplicationKey(subscription, config.public_key)) {
            const result = await request(panel.dataset.statusUrl, 'POST', { endpoint: subscription.endpoint });
            if (result.active) {
                await request(panel.dataset.saveUrl, 'POST', subscription.toJSON());
                active = true;
            }
        }
        setActive(active);
        message(active ? 'Notifikasi aktif pada perangkat ini. Anda dapat mengirim notifikasi uji.' : 'Belum aktif pada akun/perangkat ini. Tekan Aktifkan untuk memberi izin.');
    } catch {
        message('Status notifikasi belum dapat diperiksa. Muat ulang halaman untuk mencoba lagi.');
        return;
    }
    enable.addEventListener('click', async () => {
        if (busy) return;
        busy = true;
        setActive(false);
        try {
            // Keep the permission request directly inside the user's click.
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') { message('Izin belum diberikan. Notifikasi di dalam aplikasi tetap tersedia.'); return; }
            const registration = await navigator.serviceWorker.ready;
            if (subscription && !matchesApplicationKey(subscription, config.public_key)) {
                await subscription.unsubscribe(); subscription = null;
            }
            subscription ||= await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: decodeApplicationKey(config.public_key) });
            try { await request(panel.dataset.saveUrl, 'POST', subscription.toJSON()); }
            catch (error) {
                if (error.status !== 409) throw error;
                // A shared browser may have belonged to another account.
                // Create a new endpoint; never take over its existing record.
                await subscription.unsubscribe();
                subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: decodeApplicationKey(config.public_key) });
                await request(panel.dataset.saveUrl, 'POST', subscription.toJSON());
            }
            setActive(true);
            message('Notifikasi perangkat aktif. Pengiriman baru biasanya diproses dalam sekitar satu menit.');
        } catch (error) { message(error.message || 'Browser gagal mengaktifkan notifikasi. Silakan coba lagi.'); }
        finally { busy = false; enable.disabled = disable.disabled = test.disabled = false; }
    });
    disable.addEventListener('click', async () => {
        if (busy || !subscription) return;
        busy = true; setActive(true);
        try {
            await request(panel.dataset.deleteUrl, 'DELETE', { endpoint: subscription.endpoint });
            await subscription.unsubscribe(); subscription = null;
            setActive(false);
            message('Notifikasi perangkat dinonaktifkan. Notifikasi dalam aplikasi tetap tersedia.');
        } catch (error) { message(error.message); }
        finally { busy = false; enable.disabled = disable.disabled = test.disabled = false; }
    });
    test.addEventListener('click', async () => {
        if (busy || !subscription) return;
        busy = true; setActive(true);
        try {
            const data = await request(panel.dataset.testUrl, 'POST', { endpoint: subscription.endpoint });
            message(data.message);
        } catch (error) { message(error.message); }
        finally { busy = false; enable.disabled = disable.disabled = test.disabled = false; }
    });
}
