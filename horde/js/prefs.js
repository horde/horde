/**
 * Provides the javascript for the prefs page.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

document.observe('dom:loaded', function() {
    $('appsubmit').hide();

    $('app').observe('change', function() {
        $('appswitch').submit();
    });
});
