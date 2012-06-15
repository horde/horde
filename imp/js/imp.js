/**
 * Provides basic IMP javascript functions.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var IMP_JS = {

    // Defaulting to null: menumbox_load
    keydownhandler: null,
    imgs: {},

    menuMailboxSubmit: function(clear)
    {
        var mf = $('menuform');

        if ((!this.menumbox_load || clear) &&
            $F(mf.down('SELECT[name="mailbox"]'))) {
            this.menumbox_load = true;
            mf.submit();
        }
    },

    /**
     * Use DOM manipulation to un-block images.
     */
    unblockImages: function(e)
    {
        var callback,
            elt = e.element().up('.mimeStatusMessageTable').up(),
            iframe = elt.up('.mimePartBase').down('.mimePartData IFRAME.htmlMsgData'),
            iframeid = iframe.readAttribute('id'),
            imgload = false,
            s = new Selector('[htmlimgblocked]'),
            s2 = new Selector('[htmlcssblocked]'),
            s3 = new Selector('STYLE[type="text/x-imp-cssblocked"]');

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
                ++this.imgs[iframeid];
                img.setAttribute('src', src);
                imgload = true;
            } else {
                if (img.getAttribute('background')) {
                    img.setAttribute('background', src);
                }
                if (img.style.backgroundImage) {
                    if (img.style.setProperty) {
                        img.style.setProperty('background-image', 'url(' + src + ')', '');
                    } else {
                        // IE workaround
                        img.style.backgroundImage = 'url(' + src + ')';
                    }
                }
            }
        }, this);

        s2.findElements(iframe.contentWindow.document).each(function(link) {
            link.setAttribute('href', link.getAttribute('htmlcssblocked'));
        });

        s3.findElements(iframe.contentWindow.document).each(function(style) {
            style.setAttribute('type', 'text/css');
        });

        if (!imgload) {
            this.iframeResize(iframeid);
        }
    },

    imgOnload: function(id)
    {
        if (!(--this.imgs[id])) {
            this.iframeResize(id);
        }
    },

    iframeInject: function(id, data)
    {
        if (!(id = $(id))) {
            return;
        }

        var d = id.contentWindow.document;

        id.observe('load', function(i) {
            i.stopObserving('load');
            this.iframeResize.bind(this, i).defer(0.3);
        }.bind(this, id));

        d.open();
        d.write(data);
        d.close();

        if (this.keydownhandler) {
            if (d.addEventListener) {
                d.addEventListener('keydown', this.keydownhandler.bindAsEventListener(this), false);
            } else {
                d.attachEvent('onkeydown', this.keydownhandler.bindAsEventListener(this));
            }
        }

        id.show().previous().remove();

        this.iframeResize(id);
    },

    iframeResize: function(id)
    {
        if (!(id = $(id))) {
            return;
        }

        var lc = id.contentWindow.document.lastChild,
            body = id.contentWindow.document.body;

        lc = (lc.scrollHeight > body.scrollHeight) ? lc : body;

        // Try expanding IFRAME if we detect a scroll.
        if (lc.clientHeight != lc.scrollHeight ||
            id.clientHeight != lc.clientHeight) {
            id.setStyle({ height: lc.scrollHeight + 'px' });
            if (lc.clientHeight != lc.scrollHeight) {
                // Finally, brute force if it still isn't working.
                id.setStyle({ height: (lc.scrollHeight + 25) + 'px' });
            }
        }
    },

    printWindow: function(win)
    {
        /* Prototypejs not available in this window. */
        var fs = win.document.getElementById('frameset');
        if (fs) {
            fs.rows = (win.document.getElementById('headers').contentWindow.document.getElementById('headerblock').offsetHeight + 5) + 'px,*';
        }
        win.print();
        win.close();
    },

    onDomLoad: function()
    {
        // If menu is present, attach event handlers to mailbox switcher.
        var tmp = $('openmboxicon');
        if (tmp) {
            // Observe actual element since IE does not bubble change events.
            $('menu').down('[name=mailbox]').observe('change', this.menuMailboxSubmit.bind(this));
            tmp.down().observe('click', this.menuMailboxSubmit.bind(this, true));
        }
    }

};

document.observe('dom:loaded', IMP_JS.onDomLoad.bind(IMP_JS));
