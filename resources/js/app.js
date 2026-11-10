import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { subscribeToCoachingMessages } from './coaching-messages.js';
import { initializeInventarisInspection } from './inventaris-inspection.js';
import { initializeSerahTerimaInventory } from './serah-terima-inventaris.js';
import { initializeUserProfilePhoto } from './user-profile-photo.js';
import { createDinasRosterPreview } from './admin/dinas-roster-preview.js';

window.Alpine = Alpine;

Alpine.data('dinasRosterPreview', createDinasRosterPreview);

Alpine.start();
initializeInventarisInspection();
initializeSerahTerimaInventory();
initializeUserProfilePhoto();

const inbox = document.querySelector('[data-coaching-message-inbox]');
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (inbox && inbox.dataset.userId && reverbKey) {
    window.Pusher = Pusher;

    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
    const echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });

    const unsubscribe = subscribeToCoachingMessages(
        echo,
        inbox.dataset.userId,
        () => {
            document.querySelectorAll('[data-coaching-unread-count]').forEach((counter) => {
                const nextCount = Number(counter.dataset.count ?? 0) + 1;
                counter.dataset.count = String(nextCount);
                counter.textContent = `${nextCount} ${counter.dataset.label ?? ''}`.trim();
                counter.hidden = false;
            });
        },
    );

    let cleanedUp = false;
    const cleanup = () => {
        if (cleanedUp) {
            return;
        }

        cleanedUp = true;
        unsubscribe();
        echo.disconnect();
        observer.disconnect();
        window.removeEventListener('pagehide', cleanup);
    };

    const observer = new MutationObserver(() => {
        if (!inbox.isConnected) {
            cleanup();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
    window.addEventListener('pagehide', cleanup, { once: true });
}
