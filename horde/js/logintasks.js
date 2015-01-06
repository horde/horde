/**
 * Provides the javascript for the logintasks confirmation page.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
 */

document.observe('dom:loaded', function() {
    $('logintasks_skip').show().observe('click', function() {
        $('logintasks_confirm').getInputs('checkbox').invoke('setValue', 0);
        $('logintasks_confirm').submit();
    });
});
