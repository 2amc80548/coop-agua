import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class', 

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {

                primary: "hsl(var(--primary))",
                "primary-hover": "hsl(var(--primary-hover))",
                background: "hsl(var(--background))",
                "background-secondary": "hsl(var(--background-secondary))",
                surface: "hsl(var(--surface))",
                text: "hsl(var(--text))",
                "text-secondary": "hsl(var(--text-secondary))",
                border: "hsl(var(--border))",
            },
        },
    },
    plugins: [forms, typography],
};