import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const host = window.REVERB_HOST || import.meta.env.VITE_REVERB_HOST;
const port = window.REVERB_PORT || import.meta.env.VITE_REVERB_PORT;
const scheme = window.REVERB_SCHEME || import.meta.env.VITE_REVERB_SCHEME || 'https';

// Jangan inisialisasi Echo jika host tidak tersedia (broadcast dinonaktifkan)
if (!host) {
    console.info('[Echo] Broadcast dinonaktifkan — WebSocket tidak diinisialisasi.');
    window.Echo = null;
} else {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: window.REVERB_APP_KEY || import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: host,
        wsPort: port ? parseInt(port, 10) : 80,
        wssPort: port ? parseInt(port, 10) : 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
