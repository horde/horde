/**
 * Provides basic IMP javascript functions.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var IMP_JS = {

    keydownhandler: null,

    /**
     * Use DOM manipulation to un-block images.
     */
    unblockImages: function(e)
    {
        var a, callback, doc,
            elt = e.element(),
            box = elt.up('.mimeStatusMessageTable').up(),
            iframe = elt.up('.mimePartBase').down('.mimePartData IFRAME.htmlMsgData'),
            imgload = false;

        e.stop();

        if (elt.hasClassName('noUnblockImageAdd')) {
            box.slideUp({
                afterFinish: function() { box.remove(); },
                duration: 0.6
            });
        } else {
            a = new Element('A')
                .insert(IMP_JS.unblock_image_text)
                .observe('click', function(e) {
                    HordeCore.doAction('imageUnblockAdd', {
                        muid: elt.readAttribute('muid')
                    });

                    box.slideUp({
                        afterFinish: function() { box.remove(); },
                        duration: 0.6
                    });
                });

            elt.up('TBODY').update(
                new Element('TR').insert(
                    new Element('TD').insert(a)
                )
            );
        }

        callback = this.iframeResize.bind(this, iframe);
        doc = iframe.contentDocument || iframe.contentWindow.document;

        Prototype.Selector.select('[htmlimgblocked]', doc).each(function(img) {
            var src = img.getAttribute('htmlimgblocked');
            if (img.getAttribute('src')) {
                img.onload = callback;
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

        Prototype.Selector.select('[htmlcssblocked]', doc).each(function(link) {
            link.setAttribute('href', link.getAttribute('htmlcssblocked'));
        });

        Prototype.Selector.select('STYLE[type="text/x-imp-cssblocked"]', doc).each(function(style) {
            style.setAttribute('type', 'text/css');
        });

        if (!imgload) {
            this.iframeResize(iframe);
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
            if (Prototype.Browser.IE && !document.addEventListener) {
                // IE8
                this.iframeResize.bind(this, i).delay(1);
            } else {
                this.iframeResize.bind(this, i).defer();
            }
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
        if (id = $(id)) {
            id.setStyle({
                height: ((Element.getHeight(id.contentWindow.document.lastChild) + 10) + 'px')
            });
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
