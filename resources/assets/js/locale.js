(function ($, window) {
    if (window.jQuery === undefined) {
        console.error('Plugin "jQuery" required by "locale.js" is missing!');
        return;
    }
    if (window.moment === undefined) {
        console.error('Plugin "moment" required by "locale.js" is missing!');
        return;
    }

    var locale = $('html').attr('lang');
    if (locale !== undefined) {
        // moment
        window.moment.locale(locale);

        // bootstrap-datepicker
        $.fn.datepicker.defaults.language = locale;
    }

})(jQuery, window);

