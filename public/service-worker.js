const appBase = new URL('.', self.location.href);
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
// Do not cache authenticated pages, proofs, receipts or API responses.
self.addEventListener('push', (event) => {
    let payload = {};
    try { payload = event.data?.json() || {}; } catch { /* Show a generic notification. */ }
    let target;
    try { target = new URL(payload.url || 'notifications', appBase); }
    catch { target = new URL('notifications', appBase); }
    const safeTarget = target.origin === appBase.origin && target.pathname.startsWith(appBase.pathname)
        ? target.href : new URL('notifications', appBase).href;
    event.waitUntil(self.registration.showNotification('EnviroPay', {
        body: 'Ada pemberitahuan baru. Buka aplikasi untuk melihat detail.',
        icon: new URL('icons/push-192.png', appBase).href,
        badge: new URL('icons/push-192.png', appBase).href,
        tag: typeof payload.tag === 'string' ? payload.tag.slice(0, 100) : 'enviropay',
        data: { url: safeTarget },
    }));
});
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil((async () => {
        let target;
        try { target = new URL(event.notification.data?.url || 'notifications', appBase); }
        catch { target = new URL('notifications', appBase); }
        if (target.origin !== appBase.origin || !target.pathname.startsWith(appBase.pathname)) target = new URL('notifications', appBase);
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windows) {
            if (new URL(client.url).origin === appBase.origin && new URL(client.url).pathname.startsWith(appBase.pathname)) {
                await client.navigate(target.href);
                return client.focus();
            }
        }
        return self.clients.openWindow(target.href);
    })());
});
