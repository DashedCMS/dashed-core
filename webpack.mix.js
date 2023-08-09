const mix = require("laravel-mix");

mix.postCss('./resources/css/filament.css', './resources/dist', [
    require('tailwindcss', './tailwind.config.js'),
])
