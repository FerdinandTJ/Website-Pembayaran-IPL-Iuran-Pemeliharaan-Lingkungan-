<?php

return [
    'subject' => env('WEB_PUSH_VAPID_SUBJECT', 'mailto:admin@example.com'),
    'public_key' => env('WEB_PUSH_VAPID_PUBLIC_KEY'),
    'private_key' => env('WEB_PUSH_VAPID_PRIVATE_KEY'),
    'key_file' => 'web-push/vapid.json',
    // Only browser push providers. Never send to arbitrary user-supplied hosts.
    'hosts' => ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com', 'notify.windows.com'],
];
