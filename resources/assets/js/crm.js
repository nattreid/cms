/* ****************************** crm.js ************************************ */

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

        flashMessage();
    }

    redraw();
    $(document).ajaxComplete(function (event, request, settings) {
        redraw();
    });
});

