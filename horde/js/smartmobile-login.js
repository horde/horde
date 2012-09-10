/**
*/

$(document).bind("pageinit", function() {
    $("#login form").on('submit', function() {
        $("#horde-login-post").val(1);
        $(this).find('input[type="submit"]').button('disable');
    });
});
