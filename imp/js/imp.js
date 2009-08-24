/**
 * Provides basic IMP javascript functions.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

document.observe('dom:loaded', function() {
    if (!window.IMP) {
        window.IMP = {};
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

    /**
     * Use DOM manipulation to un-block images.
     */
    IMP.unblockImages = function(e)
    {
        var elt = e.element().up('TABLE.mimeStatusMessage');

        elt.up().next('.htmlMessage').select('[blocked]').each(function(b) {
            var src = decodeURIComponent(b.readAttribute('blocked'));
            if (b.hasAttribute('src')) {
                b.writeAttribute('src', src);
            } else if (b.hasAttribute('background')) {
                b.writeAttribute('background', src);
            } else if (b.style.backgroundImage) {
                b.setStyle({ backgroundImage: 'url(' + src + ')' });
            }
        });

        Effect.Fade(elt, {
            afterFinish: function() { elt.remove(); },
            duration: 0.6
        });

        e.stop();
    };

    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
        tmp.down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
    }
});
