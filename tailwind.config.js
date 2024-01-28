const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            minWidth: {
                200: '200px',
                300: '300px',
                400: '400px',
            },
            width: {
                '23/48': '47.916666%',
            },
            colors: {
                'cameo': {
                    DEFAULT: '#DCC0AD',
                    50: '#FFFFFF',
                    100: '#FFFFFF',
                    200: '#FFFFFF',
                    300: '#F4ECE6',
                    400: '#E8D6CA',
                    500: '#DCC0AD',
                    600: '#CBA286',
                    700: '#BA845E',
                    800: '#9D6843',
                    900: '#764E32'
                },
                'cameo': {
                    DEFAULT: '#DCC0AD',
                    50: '#FFFFFF',
                    100: '#FFFFFF',
                    200: '#FFFFFF',
                    300: '#F4ECE6',
                    400: '#E8D6CA',
                    500: '#DCC0AD',
                    600: '#CBA286',
                    700: '#BA845E',
                    800: '#9D6843',
                    900: '#764E32'
                },
                'blossom': {
                    DEFAULT: '#D9A5C0',
                    50: '#FFFFFF',
                    100: '#FFFFFF',
                    200: '#FDFBFC',
                    300: '#F1DEE8',
                    400: '#E5C2D4',
                    500: '#D9A5C0',
                    600: '#C87EA4',
                    700: '#B85689',
                    800: '#963F6D',
                    900: '#6F2F50'
                },
                'mountbatten-pink': {
                    DEFAULT: '#937687',
                    50: '#E4DDE1',
                    100: '#DBD1D7',
                    200: '#C9BAC3',
                    300: '#B7A4AF',
                    400: '#A58D9B',
                    500: '#937687',
                    600: '#755C6B',
                    700: '#55434E',
                    800: '#362B31',
                    900: '#171215'
                },
                'bud': {
                    DEFAULT: '#ABB2A9',
                    50: '#FFFFFF',
                    100: '#FFFFFF',
                    200: '#EAECEA',
                    300: '#D5D9D4',
                    400: '#C0C5BF',
                    500: '#ABB2A9',
                    600: '#8E978B',
                    700: '#727C6F',
                    800: '#575E54',
                    900: '#3B413A'
                },
                'thunder': {
                    DEFAULT: '#363033',
                    50: '#95888F',
                    100: '#8C7D85',
                    200: '#776A70',
                    300: '#61565C',
                    400: '#4C4347',
                    500: '#363033',
                    600: '#181617',
                    700: '#000000',
                    800: '#000000',
                    900: '#000000'
                },
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
