/**
 * Provides basic IMP javascript functions.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var IMP_JS = {

    keydownhandler: null,
    imgs: {},

    /**
     * Use DOM manipulation to un-block images.
     */
    unblockImages: function(e)
    {
        var a, callback,
            elt = e.element(),
            iframe = elt.up('.mimePartBase').down('.mimePartData IFRAME.htmlMsgData'),
            iframeid = iframe.readAttribute('id'),
            imgload = false,
            s = new Selector('[htmlimgblocked]'),
            s2 = new Selector('[htmlcssblocked]'),
            s3 = new Selector('STYLE[type="text/x-imp-cssblocked"]');

        e.stop();

        a = new Element('A')
            .insert(IMP_JS.unblock_image_text)
            .observe('click', function(e) {
                var box = e.element().up('.mimeStatusMessageTable').up();

                HordeCore.doAction('imageUnblockAdd', {
                    mbox: elt.readAttribute('mailbox'),
                    uid: elt.readAttribute('uid')
                });

                box.slideUp({
                    afterFinish: function() { box.remove(); },
                    duration: 0.6
                });
            });

        e.element().up('TBODY').update(
            new Element('TR').insert(
                new Element('TD').insert(a)
            )
        );

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
    }

};
