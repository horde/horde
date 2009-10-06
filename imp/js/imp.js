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

    IMP.imgs = {};

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
        var callback, imgs,
            elt = e.element().up('TABLE.mimeStatusMessage'),
            iframe = elt.up().next('.htmlMsgData').down('IFRAME'),
            iframeid = iframe.readAttribute('id'),
            s = new Selector('[htmlimgblocked]');

        e.stop();

        Effect.Fade(elt, {
            afterFinish: function() { elt.remove(); },
            duration: 0.6
        });

        // Need to use non-prototypejs methods to work with data inside of
        // the IFRAME. Prototypejs's Selector works, but only if we use
        // the pure javascript method.
        if (s.mode != 'normal') {
            delete Selector._cache['[htmlimgblocked]'];
            s.mode = 'normal';
            s.compileMatcher();
        }

        callback = this.imgOnload.bind(this, iframeid);
        imgs = s.findElements(iframe.contentWindow.document);
        IMP.imgs[iframeid] = imgs.size();

        imgs.each(function(img) {
            img.onload = callback;
            var src = decodeURIComponent(img.getAttribute('htmlimgblocked'));
            if (img.getAttribute('src')) {
                img.setAttribute('src', src);
            } else if (img.getAttribute('background')) {
                img.setAttribute('background', src);
            } else if (img.style.backgroundImage) {
                img.style.setProperty('background-image', 'url(' + src + ')', '');
            }
        });

        // Delete this entry, because in the rare case that another selector
        // on the page uses the same expression, it will break the next time
        // it is used.
        delete Selector._cache['[htmlimgblocked]'];
    };

    IMP.imgOnload = function(id)
    {
        if (!(--IMP.imgs[id])) {
            this.iframeResize(id);
        }
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

    IMP.iframeResize = function(id, defer)
    {
        id = $(id);
        if (!defer && Prototype.Browser.IE) {
            this.iframeResize.bind(this, id, true).defer();
        } else {
            id.up().setStyle({ height: Math.max(id.contentWindow.document.body.scrollHeight, id.contentWindow.document.lastChild.scrollHeight) + 'px' });
        }
    };

    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
        tmp.down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
    }
});
