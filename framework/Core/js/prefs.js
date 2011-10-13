/**
 * Provides the javascript for the prefs page.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 */

document.observe('dom:loaded', function() {
    $('appsubmit').hide();

    $('app').observe('change', function() {
        $('appswitch').submit();
    });
});
