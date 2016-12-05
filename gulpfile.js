var gulp = require('gulp'),
    less = require('gulp-less'),
    minify = require('gulp-clean-css'),
    concat = require('gulp-concat'),
    uglify = require('gulp-uglify'),
    streamqueue = require('streamqueue');

var paths = {
    'dev': {
        'less': './resources/assets/less/',
        'css': './resources/assets/css/',
        'js': './resources/assets/js/',
        'vendor': './resources/assets/vendor/'
    },
    'production': {
        'js': './assets/js',
        'css': './assets/css',
        'lang': './assets/js/i18n'
    }
};

// *****************************************************************************
// ************************************  JS  ***********************************

var boundledJS = [
    paths.dev.vendor + 'jquery/dist/jquery.js',
    paths.dev.vendor + 'jquery-ui/jquery-ui.js',
    paths.dev.vendor + 'nette-forms/src/assets/netteForms.js',
    paths.dev.vendor + 'nette.ajax.js/nette.ajax.js',
    paths.dev.vendor + 'nette.ajax.js/extensions/confirm.ajax.js',
    paths.dev.vendor + 'jquery-ui-touch-punch/jquery.ui.touch-punch.js',
    paths.dev.vendor + 'nette-live-form-validation/live-form-validation.js',
    paths.dev.vendor + 'utils/assets/utils.js',
    paths.dev.vendor + 'bootstrap/dist/js/bootstrap.js',
    paths.dev.vendor + 'nprogress/nprogress.js',
    paths.dev.js + 'nette.nprogress.js',
    paths.dev.vendor + 'history.nette.ajax.js/client-side/history.ajax.js',
    // localize
    paths.dev.vendor + 'moment/moment.js',
    paths.dev.vendor + 'moment/locale/cs.js',
    // datagrid
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid.js',
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid-spinners.js',
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid-instant-url-refresh.js',
    paths.dev.vendor + 'happy/dist/happy.js',
    paths.dev.vendor + 'bootstrap-datepicker/dist/js/bootstrap-datepicker.js',
    paths.dev.vendor + 'jquery-ui-sortable/jquery-ui-sortable.js',
    paths.dev.vendor + 'bootstrap-select/dist/js/bootstrap-select.js',
    // form
    paths.dev.vendor + 'bootstrap-daterangepicker/daterangepicker.js',
    // crm
    paths.dev.js + 'crm.js',
    paths.dev.js + 'dockbar.js',
    paths.dev.js + 'info.js',
    // plugins
    paths.dev.vendor + 'form/assets/form.js',
    paths.dev.vendor + 'menu/assets/menu.js',
    paths.dev.vendor + 'filemanager/assets/fileManager.js'
];

var locale = {
    'cs': [
        paths.dev.vendor + 'jquery-ui/ui/i18n/datepicker-cs.js',
        paths.dev.vendor + 'bootstrap-select/dist/js/i18n/defaults-cs_CZ.js',
        paths.dev.vendor + 'bootstrap-datepicker/js/locales/bootstrap-datepicker.cs.js',
        paths.dev.js + 'locale.js'
    ]
};

gulp.task('js', function () {
    return gulp.src(paths.dev.js + '*.js')
        .pipe(concat('crm.js'))
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsBoundled', function () {
    return gulp.src(boundledJS)
        .pipe(concat('crm.boundled.js'))
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsMin', function () {
    return gulp.src(paths.dev.js + '*.js')
        .pipe(concat('crm.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsBoundledMin', function () {
    return gulp.src(boundledJS)
        .pipe(concat('crm.boundled.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsCs', function () {
    return gulp.src(locale.cs)
        .pipe(concat('crm.cs.js'))
        .pipe(gulp.dest(paths.production.lang));
});

gulp.task('jsCsMin', function () {
    return gulp.src(locale.cs)
        .pipe(concat('crm.cs.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.production.lang));
});

// *****************************************************************************
// ***********************************  CSS  ***********************************

var boundledCSS = [
    paths.dev.vendor + 'jquery-ui/themes/base/jquery-ui.css',
    paths.dev.vendor + 'font-awesome/css/font-awesome.css',
    paths.dev.vendor + 'bootstrap/dist/css/bootstrap.css',
    paths.dev.vendor + 'bootstrap/dist/css/bootstrap-theme.css',
    paths.dev.vendor + 'bootstrap-daterangepicker/daterangepicker.css',
    paths.dev.vendor + 'nprogress/nprogress.css',
    // datagrid
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid.css',
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid-spinners.css',
    paths.dev.vendor + 'happy/dist/happy.css',
    paths.dev.vendor + 'bootstrap-datepicker/dist/css/bootstrap-datepicker3.css',
    paths.dev.vendor + 'bootstrap-select/dist/css/bootstrap-select.css',
    // plugins
    paths.dev.vendor + 'filemanager/assets/fileManager.css',
    paths.dev.vendor + 'vpaginator/assets/vpaginator.less',
    // crm
    paths.dev.css + '*.css'
];

gulp.task('css', function () {
    return streamqueue({objectMode: true},
        gulp.src(paths.dev.css + '*.css'),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('crm.css'))
        .pipe(gulp.dest(paths.production.css));
});

gulp.task('cssBoundled', function () {
    return streamqueue({objectMode: true},
        gulp.src(boundledCSS),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('crm.boundled.css'))
        .pipe(gulp.dest(paths.production.css));
});

gulp.task('cssMin', function () {
    return streamqueue({objectMode: true},
        gulp.src(paths.dev.css + '*.css'),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('crm.min.css'))
        .pipe(minify({keepSpecialComments: 0}))
        .pipe(gulp.dest(paths.production.css));
});

gulp.task('cssBoundledMin', function () {
    return streamqueue({objectMode: true},
        gulp.src(boundledCSS),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('crm.boundled.min.css'))
        .pipe(minify({keepSpecialComments: 0}))
        .pipe(gulp.dest(paths.production.css));
});

// *****************************************************************************

gulp.task('watch', function () {
    gulp.watch(paths.dev.js + '*.js', ['js', 'jsBoundled', 'jsMin', 'jsBoundledMin', 'jsCs', 'jsCsMin']);
    gulp.watch(paths.dev.vendor + '*.js', ['js', 'jsBoundled', 'jsMin', 'jsBoundledMin', 'jsCs', 'jsCsMin']);

    gulp.watch(paths.dev.css + '*.css', ['css', 'cssBoundled', 'cssMin', 'cssBoundledMin']);
    gulp.watch(paths.dev.less + '*.less', ['css', 'cssBoundled', 'cssMin', 'cssBoundledMin']);
    gulp.watch(paths.dev.vendor + '*.css', ['css', 'cssBoundled', 'cssMin', 'cssBoundledMin']);
});

gulp.task('default', ['js', 'jsBoundled', 'jsMin', 'jsBoundledMin', 'jsCs', 'jsCsMin', 'css', 'cssBoundled', 'cssMin', 'cssBoundledMin', 'watch']);

