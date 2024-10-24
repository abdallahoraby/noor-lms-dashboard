let mix = require('laravel-mix');

mix.js( 'assets/src/js/frontend-group-dashboard.js', 'assets/js' )
mix.js( 'assets/src/js/admin-group-details.js', 'assets/js' )
    .browserSync( {
        proxy : 'pms.test',
        files : [
            '**/*.php',
            'assets/**/*.js',
        ],
        ghostMode : false
    } )
    .sourceMaps();
