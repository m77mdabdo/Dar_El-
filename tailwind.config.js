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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                'arabic-body': ['Tajawal', 'sans-serif'],
                'arabic-heading': ['Aref Ruqaa', 'serif'],
                heading: ['Playfair Display', 'serif'],
                body: ['Poppins', 'sans-serif'],
            },
            colors: {
                maroon: {
                    dark: '#3C0B17',
                    DEFAULT: '#601526',
                    soft: '#7A2038',
                },
                gold: {
                    DEFAULT: '#E8C39A',
                    bright: '#D4A574',
                },
                cream: {
                    DEFAULT: '#F7EFE4',
                    2: '#EFE2CE',
                },
                ink: '#2A1015',
                'rose-dust': '#9C5064',
            },
            boxShadow: {
                'dj-main': '0 20px 50px -20px rgba(60,11,23,0.45)',
            },
        },
    },

    plugins: [forms],
};
