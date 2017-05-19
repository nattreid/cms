/* ****************************** cms.js ************************************ */

$(document).ready(function () {
    $("body").removeClass("preload");

    $.nette.ext('forceRedirect', {
        success: function (payload) {
            window.console.log(payload);
            if (payload.forceRedirect) {
                window.location.href = payload.forceRedirect;
                return false;
            }
        }
    });

    $.nette.init();

    $(document).on('click', 'a[data-confirm], button[data-confirm], input[data-confirm]', function () {
        if (!confirm($(this).data('confirm'))) {
            return false;
        }
    });

    function flashMessage() {
        var time = 3000;
        $('.ipub-flash-messages .alert').each(function () {
            time += 1000;
            $(this).delay(time).fadeOut();
        });
    }

    function ckEditorLine() {
        $('textarea.ckEditorLine').ckeditor({
            height: '80px',
            toolbarGroups: [
                {name: 'basicstyles', groups: ['basicstyles']},
                {name: 'paragraph', groups: ['align']},
                {name: 'styles', groups: ['styles']},
                {name: 'colors', groups: ['colors']}
            ],
            enterMode: CKEDITOR.ENTER_BR,
            removeButtons: 'BGColor,Styles,Format,Underline,Strike,Subscript,Superscript,RemoveFormat',
            removePlugins: 'elementspath',
            resize_enabled: false,
            on: {
                change: function (evt) {
                    this.updateElement();
                }
            }
        });
    }

    function redraw() {
        $('.datagrid a').attr('data-ajax-off', 'history');

        flashMessage();
        ckEditorLine();
    }

    redraw();
    $(document).ajaxComplete(function (event, request, settings) {
        redraw();
    });
});


/* *************** dockbar.js ***************** */

$(document).ready(function () {

    $('#dockbar .dockbar-content ul.button li').click(function () {
        $('#cms-container').toggleClass('shifted');
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
