import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                dark: {
                    bg: '#0B0F19',
                    card: '#111827',
                    border: '#1F2937',
                    hover: '#1E293B',
                }
            },
            boxShadow: {
                'tactile': '0 2px 0 0 rgba(0,0,0,0.05), inset 0 1px 0 0 rgba(255,255,255,0.1)',
                'glow': '0 0 25px -5px rgba(245, 158, 11, 0.25)',
                'card': '0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03)',
                'card-dark': '0 4px 25px -2px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.05)',
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};
