/**
 * Provides the javascript for the smartmobile login script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

$(document).bind("pageinit", function() {
    $("#login form").on('submit', function() {
        $("#horde-login-post").val(1);
        $(this).find('input[type="submit"]').button('disable');
        $.mobile.showPageLoadingMsg();
    });
});
