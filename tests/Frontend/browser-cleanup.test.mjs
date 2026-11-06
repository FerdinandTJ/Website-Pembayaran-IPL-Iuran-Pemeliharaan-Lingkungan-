import test from 'node:test';
import assert from 'node:assert/strict';
import { removeRetiredPushWorker } from '../../resources/js/browser-cleanup.js';

const workerUrl = 'https://ipl.example.test/service-worker.js';

function registration(scriptURL, slot = 'active', failUnsubscribe = false) {
    const calls = [];
    return {
        calls,
        [slot]: { scriptURL },
        pushManager: { getSubscription: async () => ({ unsubscribe: async () => {
            calls.push('unsubscribe');
            if (failUnsubscribe) throw new Error('Subscription unavailable');
        } }) },
        unregister: async () => { calls.push('unregister'); return true; },
    };
}

test('removes only our retired worker and leaves unrelated applications alone', async () => {
    const old = registration(workerUrl);
    const other = registration('https://ipl.example.test/other/service-worker.js');
    await removeRetiredPushWorker({ getRegistrations: async () => [old, other] }, workerUrl);
    assert.deepEqual(old.calls, ['unsubscribe', 'unregister']);
    assert.deepEqual(other.calls, []);
});

test('also handles waiting workers on a subdirectory installation', async () => {
    const url = new URL('./service-worker.js', 'https://ipl.example.test/enviropay/manifest.webmanifest').href;
    const old = registration(url, 'waiting');
    await removeRetiredPushWorker({ getRegistrations: async () => [old] }, url);
    assert.deepEqual(old.calls, ['unsubscribe', 'unregister']);
});

test('still unregisters when unsubscribing fails and handles missing subscriptions', async () => {
    const failing = registration(workerUrl, 'active', true);
    const empty = registration(workerUrl, 'installing');
    empty.pushManager.getSubscription = async () => null;
    await removeRetiredPushWorker({ getRegistrations: async () => [failing, empty] }, workerUrl);
    assert.deepEqual(failing.calls, ['unsubscribe', 'unregister']);
    assert.deepEqual(empty.calls, ['unregister']);
});

test('does nothing if the browser does not support service workers', async () => {
    await removeRetiredPushWorker(undefined, workerUrl);
    await removeRetiredPushWorker({}, workerUrl);
});
