/* *************** dockbar.js ***************** */

$(document).ready(function () {

    $('#dockbar .dockbar-content ul.button li').click(function () {
        $('#cms-container').toggleClass('shifted');
        $(this).toggleClass('active');
    });
});