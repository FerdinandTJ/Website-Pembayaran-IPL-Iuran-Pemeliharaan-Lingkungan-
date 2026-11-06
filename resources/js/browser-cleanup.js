// Remove registrations left in browsers that used the retired notification feature.
// Match our exact worker URL so other applications on this origin are untouched.
export async function removeRetiredPushWorker(serviceWorker, workerUrl) {
    if (!serviceWorker?.getRegistrations || !workerUrl) return;

    const registrations = await serviceWorker.getRegistrations();
    await Promise.allSettled(registrations
        .filter((registration) => [registration.active, registration.waiting, registration.installing]
            .some((worker) => worker?.scriptURL === workerUrl))
        .map(async (registration) => {
            try {
                const subscription = await registration.pushManager?.getSubscription();
                if (subscription) await subscription.unsubscribe();
            } finally {
                await registration.unregister();
            }
        }));
}
