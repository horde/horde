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
        var elt = e.element().up('TABLE.mimeStatusMessage'),
            iframe = elt.up().next('.htmlMsgData'),
            s = new Selector('[htmlimgblocked]');

        // Need to use non-prototypejs methods to work with data inside of
        // the IFRAME. Prototypejs's Selector works, but only if we use
        // the pure javascript method.
        if (s.mode != 'normal') {
            delete Selector._cache['[htmlimgblocked]'];
            s.mode = 'normal';
            s.compileMatcher();
        }

        s.findElements(iframe.contentWindow.document).each(function(b) {
            var src = decodeURIComponent(b.getAttribute('htmlimgblocked'));
            if (b.getAttribute('src')) {
                b.setAttribute('src', src);
            } else if (b.getAttribute('background')) {
                b.setAttribute('background', src);
            } else if (b.style.backgroundImage) {
                b.style.setProperty('background-image', 'url(' + src + ')', '');
            }
        });

        // Delete this entry, because in the rare case that another selector
        // on the page uses the same expression, it will break the next time
        // it is used.
        delete Selector._cache['[htmlimgblocked]'];

        Effect.Fade(elt, {
            afterFinish: function() { elt.remove(); },
            duration: 0.6
        });

        e.stop();

        this.iframeResize.bind(this, iframe.readAttribute('id')).defer();
    };

    IMP.iframeInject = function(id, data)
    {
        id = $(id);
        var d = id.contentWindow.document;

        d.open();
        d.write(data);
        d.close();

        this.iframeResize(id);
    };

    IMP.iframeResize = function(id)
    {
        id = $(id);
        id.setStyle({ height: id.contentWindow.document.body.scrollHeight + 'px' });
    };

    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
        tmp.down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
    }
});
