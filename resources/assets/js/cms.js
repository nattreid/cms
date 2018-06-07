/* ****************************** cms.js ************************************ */

$(document).ready(function () {
    $("body").removeClass("preload");

    $.nette.ext('forceRedirect', {
        success: function (payload) {
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

    function redraw() {
        $('.datagrid a').attr('data-ajax-off', 'history');
        $('[data-toggle="tooltip"]').tooltip({trigger: "hover", container: 'body'});

        flashMessage();
    }

    redraw();
    $(document).ajaxComplete(function (event, request, settings) {
        redraw();
    });
});

