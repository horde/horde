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

    window.IMP.menuFolderSubmit = function(clear)
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
    window.IMP.unblockImages = function(e)
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

        Effect.Fade(elt, {
            afterFinish: function() { elt.remove(); },
            duration: 0.6
        });

        e.stop();
    };

    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', window.IMP.menuFolderSubmit.bind(window.IMP));
        tmp.down().observe('click', window.IMP.menuFolderSubmit.bind(window.IMP, true));
    }
});
