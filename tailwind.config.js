const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './resources/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/dashed/**/*.php',
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
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
