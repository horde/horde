/**
 * Provides the javascript for the prefs page.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

document.observe('dom:loaded', function() {
    $('appsubmit').hide();

    $('app').observe('change', function() {
        $('appswitch').submit();
    });
});
