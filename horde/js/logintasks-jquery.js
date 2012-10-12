/**
 * Provides the javascript for the logintasks confirmation page.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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

$(function() {
    $('#logintasks_skip').show();
});
