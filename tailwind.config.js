import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Bricolage Grotesque"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: { DEFAULT: '#1B1F3B', soft: '#4B5173', mute: '#8087A8' },
                paper: { DEFAULT: '#F7F8FC', warm: '#EEF0FA' },
            },
            boxShadow: {
                soft: '0 1px 2px rgba(27,31,59,.05), 0 8px 24px -8px rgba(79,70,229,.18)',
                lift: '0 2px 4px rgba(27,31,59,.06), 0 24px 48px -16px rgba(79,70,229,.30)',
            },
            keyframes: {
                'card-in': {
                    '0%': { opacity: '0', transform: 'translateY(-14px) scale(.96)' },
                    '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
                'ring-pulse': {
                    '0%': { boxShadow: '0 0 0 0 rgba(20,184,166,.45)' },
                    '100%': { boxShadow: '0 0 0 14px rgba(20,184,166,0)' },
                },
                'bar-grow': {
                    '0%': { transform: 'scaleY(0)' },
                    '100%': { transform: 'scaleY(1)' },
                },
            },
            animation: {
                'card-in': 'card-in .55s cubic-bezier(.2,.8,.2,1) both',
                'ring-pulse': 'ring-pulse 1.6s ease-out 3 both',
                'bar-grow': 'bar-grow .7s cubic-bezier(.2,.8,.2,1) both',
            },
        },
    },

    plugins: [forms],
};