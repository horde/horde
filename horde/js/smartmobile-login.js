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

    /* Programatically activate views that require javascript. */
    var s = $('#horde_select_view');
    if (s) {
        s.find('option[value=mobile_nojs]').remove();
        if (HordeLogin.pre_sel) {
            s.get(0).selectedIndex = s.find('option[value=' + HordeLogin.pre_sel + ']').index;
        }
        s.selectmenu('refresh');
        $('#horde_select_view_div').show();
    }
});

var HordeLogin = {};
