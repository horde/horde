/**
 * Provides the javascript for the ACL preferences management view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPLv2 (http://www.horde.org/licenses/gpl)
 */

document.observe('dom:loaded', function() {
    $('aclmbox').observe('change', function(e) {
        $($('prefs').getInputs('checkbox')).flatten().invoke('disable');
        $('change_acl_mbox').click();
    });

    /* Disable selection of container elements. */
    $('aclmbox').select('OPTION[value=""]').invoke('writeAttribute', 'disabled', true);

    $$('TABLE.prefsAclTable')[0].on('change', 'SELECT.aclTemplate', function(e, elt) {
        var acl = $F(elt);

        elt.up('TR').select('INPUT[type=checkbox]').each(function(i) {
            i.setValue(acl.include(i.value));
        });

        elt.selectedIndex = 0;
    });
});
