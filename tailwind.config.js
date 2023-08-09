import colors from 'tailwindcss/colors'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

export default {
    content: [
        './resources/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/dashed/**/*.php',
    ],
    safelist: [
        'bg-red-100',
        'text-red-800',
        'bg-blue-100',
        'text-blue-800',
        'bg-purple-100',
        'text-purple-800',
        'bg-yellow-100',
        'text-yellow-800',
        'bg-green-100',
        'text-green-800',
        'line-through',
        'text-egg-blue',
    ],
    theme: {
        extend: {
            colors: {
                danger: colors.rose,
                primary: {
                    DEFAULT: '#00D2CD',
                    50: '#8BFFFC',
                    100: '#76FFFC',
                    200: '#4DFFFB',
                    300: '#25FFFA',
                    400: '#00FBF5',
                    500: '#00D2CD',
                    600: '#009A96',
                    700: '#00625F',
                    800: '#002A29',
                    900: '#000000',
                    950: '#000000'
                },
                success: colors.green,
                warning: colors.yellow,
            },
        },
    },
    plugins: [
        forms,
        typography,
    ],
}
