/**
 * Provides basic IMP javascript.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

if (!IMP) {
    var IMP = {};
}

IMP.menuFolderSubmit = function(clear)
{
    var mf = $('menuform');

    if ((!this.menufolder_load || clear) &&
        $F(mf.down('SELECT[name="mailbox"]'))) {
        this.menufolder_load = true;
        mf.submit();
    }
};

document.observe('dom:loaded', function() {
    $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
    $('openfoldericon').down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
});
