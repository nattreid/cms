/* ****************************** info.js *********************************** */

$(document).ready(function () {
    var info = $('#info .data');
    if (info.length) {
        setInterval(function () {
            $.nette.ajax(info.data('handleurl'));
        }, info.data('refresh'));
    }
});
