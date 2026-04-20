import './bootstrap';

import Alpine from 'alpinejs';

const storedTheme = localStorage.getItem('theme');
const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

document.documentElement.dataset.theme = storedTheme || preferredTheme;

window.setTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('theme', theme);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
};

window.toggleTheme = () => {
    window.setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
};

window.notificationToasts = ({ feedUrl }) => ({
    bootstrapped: false,
    seenIds: new Set(JSON.parse(sessionStorage.getItem('notification-toast-seen') || '[]')),
    toasts: [],
    timer: null,

    start() {
        this.fetchNotifications();
        this.timer = setInterval(() => this.fetchNotifications(), 5000);
    },

    async fetchNotifications() {
        try {
            const response = await window.axios.get(feedUrl, {
                headers: { Accept: 'application/json' },
            });
            const notifications = response.data.notifications || [];

            notifications
                .slice()
                .reverse()
                .forEach((notification) => {
                    if (this.seenIds.has(notification.id)) {
                        return;
                    }

                    this.seenIds.add(notification.id);
                    this.pushToast(notification);
                });

            this.persistSeenIds();
            this.bootstrapped = true;
        } catch (error) {
            // Polling should stay silent if the user is logged out or the request fails.
        }
    },

    pushToast(notification) {
        this.toasts.push(notification);
        setTimeout(() => this.dismiss(notification.id), 5000);
    },

    dismiss(id) {
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
    },

    persistSeenIds() {
        sessionStorage.setItem(
            'notification-toast-seen',
            JSON.stringify([...this.seenIds].slice(-100)),
        );
    },
});

window.Alpine = Alpine;

Alpine.start();
