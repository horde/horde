/**
 * Provides the javascript for the acl.php script
 *
 * $Horde: imp/js/src/acl.js,v 1.3 2007/11/23 08:05:47 slusarz Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var acl_loading;

function ACLFolderChange(clear)
{
    if ($F('aclfolder')) {
        if (acl_loading == null || clear != null) {
            acl_loading = true;
            $('acl').disable();
            $('folders').submit();
        }
    }
}
