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
        var callback,
            elt = e.element().up('.mimeStatusMessageTable').up(),
            iframe = elt.up().next().down('.htmlMsgData'),
            iframeid = iframe.readAttribute('id'),
            imgload = false,
            s = new Selector('[htmlimgblocked]');

        e.stop();

        elt.slideUp({
            afterFinish: function() { elt.remove(); },
            duration: 0.6
        });

        callback = this.imgOnload.bind(this, iframeid);

        s.findElements(iframe.contentWindow.document).each(function(img) {
            var src = img.getAttribute('htmlimgblocked');
            if (img.getAttribute('src')) {
                img.onload = callback;
                ++IMP.imgs[iframeid];
                img.setAttribute('src', src);
                imgload = true;
            } else if (img.getAttribute('background')) {
                img.setAttribute('background', src);
            } else if (img.style.backgroundImage) {
                if (img.style.setProperty) {
                    img.style.setProperty('background-image', 'url(' + src + ')', '');
                } else {
                    // IE workaround
                    img.style.backgroundImage = 'url(' + src + ')';
                }
            }
        });

        if (!imgload) {
            this.iframeResize(iframeid);
        }
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

        id.show().previous().remove();
        this.iframeResize(id);
    };

    IMP.iframeResize = function(id)
    {
        if (id = $(id)) {
            id.setStyle({ height: id.contentWindow.document.lastChild.scrollHeight + 'px' });

            // For whatever reason, browsers will report different heights
            // after the initial height setting.
            this.iframeResize2.bind(this, id).defer(0.3);
        }
    };

    IMP.iframeResize2 = function(id)
    {
        var lc = id.contentWindow.document.lastChild;

        // Try expanding IFRAME if we detect a scroll.
        if (lc.clientHeight != lc.scrollHeight ||
            id.clientHeight != lc.clientHeight) {
            id.setStyle({ height: lc.scrollHeight + 'px' });
            if (lc.clientHeight != lc.scrollHeight) {
                // Finally, brute force if it still isn't working.
                id.setStyle({ height: (lc.scrollHeight + 25) + 'px' });
            }
        }
    };

    IMP.printWindow = function(win)
    {
        /* Prototypejs not available in this window. */
        var fs = win.document.getElementById('frameset');
        if (fs) {
            fs.rows = (win.document.getElementById('headers').contentWindow.document.getElementById('headerblock').offsetHeight + 5) + 'px,*';
        }
        win.print();
        win.close();
    };

    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
        tmp.down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
    }
});
