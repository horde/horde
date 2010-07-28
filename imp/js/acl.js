/**
 * Provides the javascript for the ACL preferences management view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

document.observe('dom:loaded', function() {
    $('aclfolder').observe('change', function(e) {
        $$('input[name^=acl\[]').invoke('disable');
        $('prefs').submit();
    });
});
