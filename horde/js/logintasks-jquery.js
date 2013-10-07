/**
 * Provides the javascript for the logintasks confirmation page.
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

$(document).on('click', function(e) {
    var elt = $(e.target);
    while (elt && elt.parent().length) {
        switch (elt.attr('id')) {
        case 'logintasks_skip':
            $('#logintasks_confirm input[type="checkbox"]').each(function() {
                $(this).prop('checked', false).checkboxradio('refresh');
            });
            break;
        }
        elt = elt.parent();
    }
});

$(document).bind("pageinit", function() {
    $("#logintasks form").on('submit', function() {
        $(this).find('button[type="submit"]').button('disable');
        $.mobile.showPageLoadingMsg();
    });
});

$(function() {
    $('#logintasks_skip').show();
});
