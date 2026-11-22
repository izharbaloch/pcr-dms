import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Primary brand colour: green
                brand: {
                    50: '#f0f9f2', 100: '#dcf1e1', 200: '#bbe3c6', 300: '#8dcfa2', 400: '#58b378',
                    500: '#2f9a59', 600: '#1f7d46', 700: '#1a6439', 800: '#18502f', 900: '#144228', 950: '#0a2616',
                },
                // Secondary brand colour: blue
                ocean: {
                    50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa',
                    500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a',
                },
                // Accent colour: yellow
                sun: {
                    50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24',
                    500: '#f5b301', 600: '#d99100', 700: '#b45f06',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgb(16 24 40 / 0.04), 0 1px 3px rgb(16 24 40 / 0.06)',
                pop: '0 12px 32px -8px rgb(16 24 40 / 0.18), 0 2px 6px rgb(16 24 40 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
