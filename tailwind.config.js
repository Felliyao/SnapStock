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
                sans: ['Open Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                emerald: {
                    50: '#E6F6FF',
                    100: '#CCEFFF',
                    200: '#99DFFF',
                    300: '#66CFFF',
                    400: '#33BFFF',
                    500: '#00BFFF',
                    600: '#00A3E0',
                    700: '#0089BD',
                    800: '#006F99',
                    900: '#005575',
                },
                indigo: {
                    50: '#F7F2FF',
                    100: '#EEE4FF',
                    200: '#DCCFFA',
                    300: '#BFA9F0',
                    400: '#A98EE8',
                    500: '#9370DB',
                    600: '#8258C7',
                    700: '#6D47B3',
                    800: '#5A3899',
                    900: '#482A7F',
                },
                amber: {
                    50: '#FFF5E6',
                    100: '#FFE6BF',
                    200: '#FFCF80',
                    300: '#FFB84D',
                    400: '#FFA726',
                    500: '#FF8C00',
                    600: '#E67E00',
                    700: '#CC7000',
                    800: '#B36200',
                    900: '#995400',
                },
            },
        },
    },

    plugins: [forms],
};
