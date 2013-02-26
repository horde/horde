/**
 * Provides the javascript for the logintasks confirmation page.
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

document.observe('dom:loaded', function() {
    $('logintasks_skip').show().observe('click', function() {
        $('logintasks_confirm').getInputs('checkbox').invoke('setValue', 0);
        $('logintasks_confirm').submit();
    });
});
