/* ****************************** crm.js ************************************ */

if ($.fn.button.noConflict) {
    $.fn.btn = $.fn.button.noConflict();
}

$(document).ready(function () {
    $("body").removeClass("preload");

    $.nette.init();

    $(document).on('click', 'a[data-confirm], button[data-confirm], input[data-confirm]', function () {
        if (!confirm($(this).data('confirm'))) {
            return false;
        }
    });

    function flashMessage() {
        var time = 2000;
        $('.ipub-flash-messages .alert').click(function () {
            $(this).remove();
        }).each(function () {
            time += 1000;
            $(this).delay(time).fadeOut();
        });
    }

    function redraw() {
        $('.datagrid a').attr('data-ajax-off', 'history');

        $('.tabs').tabs();

        flashMessage();
    }

    redraw();
    $(document).ajaxComplete(function (event, request, settings) {
        redraw();
    });
});


/* *************** dockbar.js ***************** */

$(document).ready(function () {

    $('#dockbar .dockbar-content ul.button li').click(function () {
        $('#crm-container').toggleClass('shifted');
        $(this).toggleClass('active');
    });
});
/* ****************************** info.js *********************************** */

$(document).ready(function () {
    var info = $('#info .data');
    if (info.length) {
        setInterval(function () {
            $.nette.ajax(info.data('handleurl'));
        }, info.data('refresh'));
    }
});

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


/**
 * NProgress extension for nette.ajax.js
 * @param {jQuery} $
 * @param {Window} window
 * @link https://github.com/rstacruz/nprogress
 */
(function ($, window) {
    "use strict";

    $.nette.ext('nprogress',
        {
            start: function () {
                window.NProgress.start();
            },

            complete: function () {
                window.NProgress.done();
            }
        });

})(jQuery, window);
