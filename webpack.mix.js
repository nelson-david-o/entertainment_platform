const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('postcss-import'),
        
    ])
    .webpackConfig(require('./webpack.config'));

// CSS
mix.styles([  
    'resources/assets/plugins/overlayScrollbars/css/OverlayScrollbars.css',
    'resources/assets/css/adminlte.css',
    'resources/assets/css/mycss.css',
    'resources/assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.css',
    'resources/assets/plugins/icheck-bootstrap/icheck-bootstrap.css',
    'resources/assets/plugins/jqvmap/jqvmap.css',
    'resources/assets/plugins/daterangepicker/daterangepicker.css',
    'resources/assets/plugins/summernote/summernote-bs4.css'
], 'public/css/all.css');
 

// DIRECTORIO DE IMAGENES
mix.copyDirectory('resources/assets/img', 'storage/app/public/img');
// DIRECTORIO DE PLUGINS DE ADMINLTE3
mix.copyDirectory('resources/assets/plugins', 'public/plugins');
// DIRECTORIO DE CSS
mix.copyDirectory('resources/assets/css', 'public/css');
// DIRECTORIO DE JAVASCRITPS
mix.copyDirectory('resources/assets/js', 'public/js');
