import "./bootstrap";

import Alpine from "alpinejs";

const storedTheme = localStorage.getItem("theme");
const preferredTheme = window.matchMedia("(prefers-color-scheme: dark)").matches
    ? "dark"
    : "light";

document.documentElement.dataset.theme = storedTheme || preferredTheme;

window.setTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem("theme", theme);
    window.dispatchEvent(new CustomEvent("theme-changed", { detail: theme }));
};

window.toggleTheme = () => {
    window.setTheme(
        document.documentElement.dataset.theme === "dark" ? "light" : "dark",
    );
};

window.notificationToasts = ({ feedUrl }) => ({
    bootstrapped: false,
    seenIds: new Set(
        JSON.parse(sessionStorage.getItem("notification-toast-seen") || "[]"),
    ),
    toasts: [],
    timer: null,

    start() {
        this.fetchNotifications();
        this.timer = setInterval(() => this.fetchNotifications(), 30000);
    },

    async fetchNotifications() {
        try {
            const response = await window.axios.get(feedUrl, {
                headers: { Accept: "application/json" },
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
            "notification-toast-seen",
            JSON.stringify([...this.seenIds].slice(-100)),
        );
    },
});

const initAutoFilterForms = () => {
    document.querySelectorAll("[data-auto-filter]").forEach((form) => {
        const delay = Number.parseInt(
            form.dataset.autoFilterDelay || "500",
            10,
        );
        let timer = null;

        const submitForm = (wait = 0) => {
            window.clearTimeout(timer);

            timer = window.setTimeout(() => {
                if (typeof form.requestSubmit === "function") {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            }, wait);
        };

        form.querySelectorAll("select, input").forEach((field) => {
            const type = (field.getAttribute("type") || "").toLowerCase();
            const isTextInput =
                ["search", "text", "email", "tel", "number"].includes(type) ||
                (field.tagName === "INPUT" && type === "");

            if (isTextInput) {
                field.addEventListener("input", () => submitForm(delay));
                return;
            }

            field.addEventListener("change", () => submitForm());
        });
    });
};

initAutoFilterForms();

window.Alpine = Alpine;

Alpine.start();
