/**
 * Provides the javascript for the logintasks confirmation page.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

document.observe('click', function(e) {
    switch (e.element().readAttribute('id')) {
    case 'logintasks_skip':
        $('logintasks_confirm').getInputs('checkbox').invoke('setValue', 0);
        $('logintasks_confirm').submit();
        break;
    }
});

document.observe('dom:loaded', function() {
    $('logintasks_skip').show();
});
