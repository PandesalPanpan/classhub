import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

async function initEcho() {
    const config = await getBroadcastingConfig();
    if (!config?.enabled || !config.key) return;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.wsHost,
        wsPort: config.wsPort ?? 80,
        wssPort: config.wssPort ?? 443,
        forceTLS: config.forceTLS ?? true,
        enabledTransports: ['ws', 'wss'],
    });
}

async function getBroadcastingConfig() {
    try {
        const response = await fetch('/api/broadcasting/config', {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) return null;
        return response.json();
    } catch {
        return null;
    }
}

initEcho();
