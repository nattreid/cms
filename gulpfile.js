var gulp = require('gulp'),
    less = require('gulp-less'),
    minify = require('gulp-clean-css'),
    concat = require('gulp-concat'),
    uglify = require('gulp-uglify'),
    modifyCssUrls = require('gulp-modify-css-urls'),
    merge = require('merge-stream'),
    streamqueue = require('streamqueue');

var paths = {
    'dev': {
        'less': './resources/assets/less/',
        'css': './resources/assets/css/',
        'js': './resources/assets/js/',
        'vendor': './node_modules/'
    },
    'production': {
        'js': './assets/js',
        'css': './assets/css',
        'lang': './assets/js/i18n'
    }
};

// *****************************************************************************
// ************************************  JS  ***********************************

var bundledJS = [
    paths.dev.vendor + 'jquery/dist/jquery.js',
    paths.dev.vendor + 'jquery-ui-dist/jquery-ui.js',
    paths.dev.vendor + 'nette.ajax.js/nette.ajax.js',
    paths.dev.vendor + 'nette.ajax.js/extensions/confirm.ajax.js',
    paths.dev.vendor + 'jquery-ui-touch-punch/jquery.ui.touch-punch.js',
    paths.dev.vendor + 'live-form-validation/live-form-validation.js',
    paths.dev.vendor + 'nattreid-utils/assets/utils.js',
    paths.dev.vendor + 'bootstrap/dist/js/bootstrap.js',
    paths.dev.vendor + 'nprogress/nprogress.js',
    paths.dev.js + 'nette.nprogress.js',
    paths.dev.vendor + 'history.nette.ajax.js/client-side/history.ajax.js',
    // localize
    paths.dev.vendor + 'moment/moment.js',
    paths.dev.vendor + 'moment/locale/cs.js',
    // spectrum
    paths.dev.vendor + 'spectrum-colorpicker/spectrum.js',
    // datagrid
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid.js',
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid-spinners.js',
    paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid-instant-url-refresh.js',
    paths.dev.vendor + 'happy/dist/happy.js',
    paths.dev.vendor + 'bootstrap-datepicker/dist/js/bootstrap-datepicker.js',
    paths.dev.vendor + 'bootstrap-select/dist/js/bootstrap-select.js',
    // form
    paths.dev.vendor + 'bootstrap-daterangepicker/daterangepicker.js',
    paths.dev.vendor + 'corejs-typeahead/dist/typeahead.bundle.js',
    // ckeditor
    paths.dev.vendor + 'ckeditor-full/adapters/jquery.js',
    // cms
    paths.dev.js + 'cms.js',
    paths.dev.js + 'dockbar.js',
    paths.dev.js + 'info.js',
    // plugins
    paths.dev.vendor + 'nattreid-form/assets/form.js',
    paths.dev.vendor + 'nattreid-menu/assets/menu.js',
    paths.dev.vendor + 'nattreid-file-manager/assets/fileManager.js',
    paths.dev.vendor + 'nattreid-modal/assets/modal.js'
];

var locale = {
    'cs': [
        paths.dev.vendor + 'jquery-ui/ui/i18n/datepicker-cs.js',
        paths.dev.vendor + 'bootstrap-select/dist/js/i18n/defaults-cs_CZ.js',
        paths.dev.vendor + 'bootstrap-datepicker/js/locales/bootstrap-datepicker.cs.js',
        paths.dev.js + 'locale.js'
    ],
    'en': [
        paths.dev.vendor + 'jquery-ui/ui/i18n/datepicker-en-GB.js',
        paths.dev.vendor + 'bootstrap-select/dist/js/i18n/defaults-en_US.js',
        paths.dev.vendor + 'bootstrap-datepicker/js/locales/bootstrap-datepicker.en-GB.js',
        paths.dev.js + 'locale.js'
    ]
};

gulp.task('js', function () {
    return gulp.src(paths.dev.js + '*.js')
        .pipe(concat('cms.js'))
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsBundled', function () {
    return gulp.src(bundledJS)
        .pipe(concat('cms.bundled.js'))
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsMin', function () {
    return gulp.src(paths.dev.js + '*.js')
        .pipe(concat('cms.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsBundledMin', function () {
    return gulp.src(bundledJS)
        .pipe(concat('cms.bundled.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.production.js));
});

gulp.task('jsLocale', function () {
    var streams = [];
    for (var lang in locale) {
        var stream = gulp.src(locale[lang])
            .pipe(concat('cms.' + lang + '.js'))
            .pipe(gulp.dest(paths.production.lang));
        streams.push(stream);
    }
    return merge.apply(this, streams);
});

gulp.task('jsLocaleMin', function () {
    var streams = [];
    for (var lang in locale) {
        var stream = gulp.src(locale[lang])
            .pipe(concat('cms.' + lang + '.min.js'))
            .pipe(uglify())
            .pipe(gulp.dest(paths.production.lang));
        streams.push(stream);
    }
    return merge.apply(this, streams);
});

// *****************************************************************************
// ***********************************  CSS  ***********************************

function getBundledCSS() {
    return streamqueue.obj(
        gulp.src(paths.dev.vendor + 'font-awesome/css/font-awesome.css')
            .pipe(modifyCssUrls({
                modify: function (url, filePath) {
                    return url.replace('../fonts/', '/fonts/font-awesome/');
                }
            })),
        gulp.src(paths.dev.vendor + 'bootstrap/dist/css/bootstrap.css')
            .pipe(modifyCssUrls({
                modify: function (url, filePath) {
                    return url.replace('../fonts/', '/fonts/bootstrap/');
                }
            })),
        gulp.src(paths.dev.vendor + 'nattreid-file-manager/assets/fileManager.css')
            .pipe(modifyCssUrls({
                modify: function (url, filePath) {
                    return url.replace('/images/', '/images/fileManager/');
                }
            })),
        gulp.src([
            paths.dev.vendor + 'jquery-ui-dist/jquery-ui.theme.css',
            paths.dev.vendor + 'bootstrap/dist/css/bootstrap-theme.css',
            paths.dev.vendor + 'bootstrap-daterangepicker/daterangepicker.css',
            paths.dev.vendor + 'nprogress/nprogress.css',
            // datagrid
            paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid.css',
            paths.dev.vendor + 'ublaboo-datagrid/assets/dist/datagrid-spinners.css',
            paths.dev.vendor + 'happy/dist/happy.css',
            paths.dev.vendor + 'bootstrap-datepicker/dist/css/bootstrap-datepicker3.css',
            paths.dev.vendor + 'bootstrap-select/dist/css/bootstrap-select.css',
            // spectrum
            paths.dev.vendor + 'spectrum-colorpicker/spectrum.css',
            // plugins
            paths.dev.vendor + 'nattreid-form/assets/form.css',
            paths.dev.vendor + 'nattreid-modal/assets/modal.min.css',
            // cms
            paths.dev.css + '*.css'
        ])
    );
}

gulp.task('css', function () {
    return streamqueue.obj(
        gulp.src(paths.dev.css + '*.css'),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('cms.css'))
        .pipe(gulp.dest(paths.production.css));
});

gulp.task('cssBundled', function () {
    return streamqueue.obj(
        getBundledCSS(),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('cms.bundled.css'))
        .pipe(gulp.dest(paths.production.css));
});

gulp.task('cssMin', function () {
    return streamqueue.obj(
        gulp.src(paths.dev.css + '*.css'),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('cms.min.css'))
        .pipe(minify({keepSpecialComments: 0}))
        .pipe(gulp.dest(paths.production.css));
});

gulp.task('cssBundledMin', function () {
    return streamqueue.obj(
        getBundledCSS(),
        gulp.src(paths.dev.less + '*.less')
            .pipe(less())
    )
        .pipe(concat('cms.bundled.min.css'))
        .pipe(minify({keepSpecialComments: 0}))
        .pipe(gulp.dest(paths.production.css));
});

// *****************************************************************************

gulp.task('watch', function () {
    gulp.watch(paths.dev.js + '*.js', gulp.series('js', 'jsBundled', 'jsMin', 'jsBundledMin', 'jsLocale', 'jsLocaleMin'));
    gulp.watch(paths.dev.vendor + '*.js', gulp.series('js', 'jsBundled', 'jsMin', 'jsBundledMin', 'jsLocale', 'jsLocaleMin'));

    gulp.watch(paths.dev.css + '*.css', gulp.series('css', 'cssBundled', 'cssMin', 'cssBundledMin'));
    gulp.watch(paths.dev.less + '*.less', gulp.series('css', 'cssBundled', 'cssMin', 'cssBundledMin'));
    gulp.watch(paths.dev.vendor + '*.css', gulp.series('css', 'cssBundled', 'cssMin', 'cssBundledMin'));
});

gulp.task('default', gulp.series('js', 'jsBundled', 'jsMin', 'jsBundledMin', 'jsLocale', 'jsLocaleMin', 'css', 'cssBundled', 'cssMin', 'cssBundledMin', 'watch'));

