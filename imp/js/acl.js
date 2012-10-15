/**
 * Provides the javascript for the ACL preferences management view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

document.observe('dom:loaded', function() {
    $('aclmbox').observe('change', function(e) {
        $($('prefs').getInputs('checkbox')).flatten().invoke('disable');
        $('change_acl_mbox').click();
    });

    $$('TABLE.prefsAclTable')[0].on('change', 'SELECT.aclTemplate', function(e, elt) {
        var acl = $F(elt);

        elt.up('TR').select('INPUT[type=checkbox]').each(function(i) {
            i.setValue(acl.include(i.value));
        });

        elt.selectedIndex = 0;
    });
});
