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
});
