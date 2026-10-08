import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Dizayn tizimi "Surovnoma" tokenlari: qiymatlar resources/css/app.css da (--c-* RGB kanallari),
// kunduzgi/tungi mavzu va rol aksenti ([data-role]) shu yerdan avtomatik almashadi.
const token = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

const scale = (map) => Object.fromEntries(Object.entries(map).map(([step, name]) => [step, token(name)]));

const neutral = scale({
    50: 'canvas', 100: 'surface-sunken', 200: 'line', 300: 'line-strong', 400: 'muted', 500: 'muted',
    600: 'muted', 700: 'ink', 800: 'ink', 900: 'ink', 950: 'ink',
});

const accent = scale({
    50: 'accent-soft', 100: 'accent-soft', 200: 'accent-soft', 300: 'accent', 400: 'accent', 500: 'accent',
    600: 'accent', 700: 'accent', 800: 'accent-strong', 900: 'accent-strong', 950: 'accent-strong',
});

const status = (key) => scale({
    50: `status-${key}-bg`, 100: `status-${key}-bg`, 200: `status-${key}-bg`, 300: `status-${key}-dot`,
    400: `status-${key}-dot`, 500: `status-${key}-dot`, 600: `status-${key}-dot`, 700: `status-${key}-fg`,
    800: `status-${key}-fg`, 900: `status-${key}-fg`, 950: `status-${key}-fg`,
});

const roleAdmin = scale({
    50: 'role-admin-soft', 100: 'role-admin-soft', 200: 'role-admin-soft', 300: 'role-admin', 400: 'role-admin',
    500: 'role-admin', 600: 'role-admin', 700: 'role-admin', 800: 'role-admin-strong', 900: 'role-admin-strong', 950: 'role-admin-strong',
});

const colors = {
    slate: neutral,
    gray: neutral,
    zinc: neutral,
    stone: neutral,
    neutral,
    cyan: accent,
    teal: accent,
    sky: accent,
    indigo: accent,
    red: status('new'),
    rose: status('new'),
    amber: status('assigned'),
    yellow: status('assigned'),
    orange: status('in-progress'),
    emerald: status('completed'),
    green: status('completed'),
    violet: roleAdmin,
    purple: roleAdmin,
    canvas: token('canvas'),
    surface: token('surface'),
    sunken: token('surface-sunken'),
    ink: token('ink'),
    muted: token('muted'),
    line: token('line'),
    'line-strong': token('line-strong'),
    accent: {
        DEFAULT: token('accent'),
        strong: token('accent-strong'),
        soft: token('accent-soft'),
        on: token('on-accent'),
    },
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            colors,
            // To'q fonlar (slate-800/900) — asosiy tugmalar: joriy rol aksenti.
            backgroundColor: {
                white: token('surface'),
                slate: { ...neutral, 800: token('accent-strong'), 900: token('accent') },
                gray: { ...neutral, 700: token('accent-strong'), 800: token('accent'), 900: token('accent') },
            },
            borderColor: {
                white: token('surface'),
            },
            textColor: {
                white: token('on-accent'),
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"IBM Plex Sans Condensed"', '"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                sm: '4px',
                md: '6px',
                lg: '8px',
                xl: '10px',
                '2xl': '10px',
                '3xl': '14px',
            },
            boxShadow: {
                sm: '0 1px 2px rgba(20, 21, 23, 0.08)',
                DEFAULT: '0 1px 2px rgba(20, 21, 23, 0.08)',
                md: '0 1px 2px rgba(20, 21, 23, 0.08)',
                lg: '0 12px 32px -8px rgba(20, 21, 23, 0.28)',
                xl: '0 12px 32px -8px rgba(20, 21, 23, 0.28)',
                '2xl': '0 12px 32px -8px rgba(20, 21, 23, 0.28)',
            },
        },
    },

    plugins: [forms],
};
