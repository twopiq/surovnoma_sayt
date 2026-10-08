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

const spawnThemeRipple = (x, y, radius) => {
    [0, 140].forEach((delay) => {
        const ring = document.createElement("span");
        ring.className = "theme-ripple";
        ring.style.left = `${x}px`;
        ring.style.top = `${y}px`;
        ring.style.setProperty("--ripple-size", `${radius * 2}px`);
        ring.style.animationDelay = `${delay}ms`;
        document.body.appendChild(ring);
        ring.addEventListener("animationend", () => ring.remove());
    });
};

window.toggleTheme = (event) => {
    const nextTheme =
        document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    if (!document.startViewTransition || reduceMotion) {
        window.setTheme(nextTheme);
        return;
    }

    const rect = event?.currentTarget?.getBoundingClientRect?.();
    const x = rect ? rect.left + rect.width / 2 : window.innerWidth / 2;
    const y = rect ? rect.top + rect.height / 2 : window.innerHeight / 2;
    const radius = Math.hypot(
        Math.max(x, window.innerWidth - x),
        Math.max(y, window.innerHeight - y),
    );

    const transition = document.startViewTransition(() =>
        window.setTheme(nextTheme),
    );

    transition.ready.then(() => {
        document.documentElement.animate(
            {
                clipPath: [
                    `circle(0px at ${x}px ${y}px)`,
                    `circle(${radius}px at ${x}px ${y}px)`,
                ],
            },
            {
                duration: 900,
                easing: "cubic-bezier(0.22, 1, 0.36, 1)",
                pseudoElement: "::view-transition-new(root)",
            },
        );
        spawnThemeRipple(x, y, radius);
    });
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
