const mix = require('laravel-mix');

mix
    .sass('./resources/scss/bootstrap.scss', './resources/dist/base.css', [])
    .js('./resources/js/admin.js', './resources/dist/admin.js')
	.sourceMaps();
