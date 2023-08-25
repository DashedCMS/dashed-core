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
        'text-red-800',
        'text-blue-800',
        'text-purple-800',
        'text-yellow-800',
        'text-green-800',
        'text-red-700',
        'text-blue-700',
        'text-purple-700',
        'text-yellow-700',
        'text-green-700',
        'text-red-600',
        'text-blue-600',
        'text-purple-600',
        'text-yellow-600',
        'text-green-600',
        'text-red-500',
        'text-blue-500',
        'text-purple-500',
        'text-yellow-500',
        'text-green-500',
        'text-red-800',
        'text-blue-800',
        'text-purple-800',
        'text-yellow-800',
        'text-green-800',
        'bg-red-100',
        'bg-blue-100',
        'bg-purple-100',
        'bg-yellow-100',
        'bg-green-100',
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
