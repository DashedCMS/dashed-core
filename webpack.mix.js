const mix = require("laravel-mix");
const tailwindcss = require('tailwindcss');

// mix.options({
//     processCssUrls: false,
//     postCss: [tailwindcss('./tailwind.config.js')],
// }).sass('resources/assets/css/app.scss', 'public/assets/css')
// .js('resources/assets/js/app.js', 'public/assets/js/app.js')
//     .copy('resources/assets/js/includes/toastr.js', 'public/assets/js/toastr.js')
//     .copy('resources/assets/fonts', 'public/assets/fonts')
//     .copy('resources/assets/files', 'public/assets/files')
//     .version();

mix.postCss('./resources/assets/css/app.css', './resources/dist/css', [
    require('tailwindcss', './tailwind.config.js'),
])
