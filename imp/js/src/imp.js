/**
 * Provides basic IMP javascript functions.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var IMP = window.IMP || {};

IMP.menuFolderSubmit = function(clear)
{
    var mf = $('menuform');

    if ((!this.menufolder_load || clear) &&
        $F(mf.down('SELECT[name="mailbox"]'))) {
        this.menufolder_load = true;
        mf.submit();
    }
};

/**
 * Use DOM manipulation to un-block images.
 */
IMP.unblockImages = function(e)
{
    var elt = e.element().up('TABLE.mimeStatusMessage');

    elt.next('.htmlMessage').select('[blocked]').each(function(e) {
        var src = decodeURIComponent(e.readAttribute('blocked'));
        if (e.hasAttribute('src')) {
            e.writeAttribute('src', src);
        } else if (e.hasAttribute('background')) {
            e.writeAttribute('background', src);
        } else if (e.style.backgroundImage) {
            e.setStyle({ backgroundImage: 'url(' + src + ')' });
        }
    });

    Effect.Fade(elt, { duration: 0.6, afterFinish: function() { elt.remove(); } });

    e.stop();
};

document.observe('dom:loaded', function() {
    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
        tmp.down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
    }
});
