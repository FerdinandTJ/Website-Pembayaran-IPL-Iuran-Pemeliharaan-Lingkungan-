import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';
import { decodeApplicationKey, matchesApplicationKey } from '../../resources/js/web-push.js';

test('VAPID URL-safe base64 is decoded and rotated subscription keys are detected', () => {
    const bytes = Uint8Array.from([4, 250, 251, 255, 0, 1]);
    const key = Buffer.from(bytes).toString('base64url');
    assert.deepEqual(decodeApplicationKey(key), bytes);
    assert.equal(matchesApplicationKey({ options: { applicationServerKey: bytes.buffer } }, key), true);
    assert.equal(matchesApplicationKey({ options: { applicationServerKey: new Uint8Array([4, 1]).buffer } }, key), false);
});
function worker() {
    const listeners = new Map();
    const shown = [];
    const opened = [];
    const self = {
        location: { href: 'https://example.com/service-worker.js' },
        addEventListener: (type, callback) => listeners.set(type, callback),
        registration: { showNotification: async (title, options) => shown.push({ title, options }) },
        clients: { matchAll: async () => [], openWindow: async (url) => opened.push(url) },
    };
    vm.runInNewContext(readFileSync(new URL('../../public/service-worker.js', import.meta.url), 'utf8'), { self, URL });
    return { listeners, shown, opened };
}
test('push keeps lockscreen text generic and refuses external navigation payloads', async () => {
    const { listeners, shown } = worker();
    let work;
    listeners.get('push')({ data: { json: () => ({ body: 'Nama rahasia Rp 1.000.000', url: 'https://evil.example/steal', tag: 'safe-id' }) }, waitUntil: (promise) => { work = promise; } });
    await work;
    assert.equal(shown[0].title, 'EnviroPay');
    assert.equal(shown[0].options.body.includes('rahasia'), false);
    assert.equal(shown[0].options.data.url, 'https://example.com/notifications');
    assert.equal(listeners.has('fetch'), false);
});
test('malformed payload URLs fall back safely and notification click only opens app origin', async () => {
    const { listeners, shown, opened } = worker();
    let work;
    listeners.get('push')({ data: { json: () => ({ url: 'http://[' }) }, waitUntil: (promise) => { work = promise; } });
    await work;
    assert.equal(shown[0].options.data.url, 'https://example.com/notifications');
    listeners.get('notificationclick')({ notification: { close() {}, data: { url: 'javascript:alert(1)' } }, waitUntil: (promise) => { work = promise; } });
    await work;
    assert.equal(opened[0], 'https://example.com/notifications');
});
