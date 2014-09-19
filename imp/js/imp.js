/**
 * Basic IMP javascript functions.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var IMP_JS = {

    // lazyload_run,

    /**
     * Use DOM manipulation to un-block images.
     */
    unblockImages: function(e)
    {
        var a, callback, doc,
            elt = e.element(),
            box = elt.up('.mimeStatusMessageTable').up(),
            iframe = elt.up('.mimePartBase').down('.mimePartData IFRAME.htmlMsgData');

        e.stop();

        if (elt.hasClassName('noUnblockImageAdd')) {
            box.slideUp({
                afterFinish: function() { box.remove(); },
                duration: 0.6
            });
        } else {
            a = new Element('A')
                .insert(IMP_JS.unblock_image_text)
                .observe('click', function() {
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

        callback = this.iframeResize.bindAsEventListener(this, iframe);
        doc = this.iframeDoc(iframe);

        Prototype.Selector.select('[htmlimgblocked]', doc).each(function(img) {
            var src = img.getAttribute('htmlimgblocked');
            img.removeAttribute('htmlimgblocked');

            if (img.getAttribute('src')) {
                img.onload = callback;
                img.setAttribute('data-src', src);
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

        Prototype.Selector.select('[htmlimgblocked_srcset]', doc).each(function(img) {
            img.setAttribute('srcset', img.getAttribute('htmlimgblocked_srcset'));
            img.removeAttribute('htmlimgblocked_srcset');
        });

        Prototype.Selector.select('[htmlcssblocked]', doc).each(function(link) {
            link.setAttribute('href', link.getAttribute('htmlcssblocked'));
            link.removeAttribute('htmlcssblocked');
        });

        Prototype.Selector.select('STYLE[type="text/x-imp-cssblocked"]', doc).each(function(style) {
            style.setAttribute('type', 'text/css');
        });

        this.iframeResize(null, iframe);
    },

    iframeInject: function(id, data)
    {
        if (!(id = $(id))) {
            return;
        }

        var d = this.iframeDoc(id), ev;

        id.onload = function(e) {
            this.iframeResize(e, id);
            id.setStyle({ overflowY: '' });
        }.bind(this);

        d.open();
        d.write(data);
        d.close();

        ev = function(name, e) {
            id.fire('IMP_JS:' + name, e);
        };

        if (d.addEventListener) {
            d.addEventListener('click', ev.curry('htmliframe_click'), false);
            d.addEventListener('keydown', ev.curry('htmliframe_keydown'), false);
        } else {
            d.attachEvent('onclick', ev.curry('htmliframe_click'));
            d.attachEvent('onkeydown', ev.curry('htmliframe_keydown'));
        }

        id.setStyle({ overflowY: 'hidden' });
        id.show().previous().remove();
        this.iframeResize(null, id);
    },

    iframeResize: function(e, id)
    {
        var body, h, html;

        if (e) {
            delete Event.element(e).onload;
        }

        id = $(id);
        if (id) {
            body = this.iframeDoc(id).body;
            html = body.parentNode;

            Element.setStyle(body, { height: null });

            h = Math.max(
                body.offsetHeight,
                // IE 8 only
                (Prototype.Browser.IE && !document.addEventListener) ? body.scrollHeight : 0,
                html.offsetHeight,
                html.scrollHeight
            );

            if (html.scrollHeight != html.clientHeight) {
                h += 25;
            }

            id.setStyle({ height: h + 'px' });

            this.iframeImgLazyLoad(id);
        }
    },

    iframeImgLazyLoad: function(iframe)
    {
        if (!this.lazyload_run) {
            this.lazyload_run = true;
            this.iframeImgLazyLoadRun.bind(this, iframe).delay(0.1);
        }
    },

    iframeImgLazyLoadRun: function(iframe)
    {
        var imgs, mb_height, mb_pad, range_top, range_bottom, resize,
            mb = $('messageBody');

        resize = this.iframeResize.bindAsEventListener(this, iframe);

        mb_height = mb.getHeight();
        /* Load messages within 10% of range boundaries. */
        mb_pad = parseInt(mb_height * 0.1, 10);

        range_top = mb.scrollTop + mb.cumulativeOffset().top - iframe.cumulativeOffset().top - mb_pad;
        range_bottom = range_top + mb_height + mb_pad;

        imgs = Prototype.Selector.select('IMG[data-src]', this.iframeDoc(iframe)).findAll(Element.visible);
        imgs.each(function(img) {
            var co = Element.cumulativeOffset(img);
            if (co.top > range_top && co.top < range_bottom) {
                img.onload = resize;
                Element.writeAttribute(img, 'src', Element.readAttribute(img, 'data-src'));
                Element.writeAttribute(img, 'data-src', null);
            }
        });

        this.lazyload_run = false;
    },

    iframeDoc: function(i)
    {
        return i.contentDocument || i.contentWindow.document;
    },

    printWindow: function(win)
    {
        win.print();
        // Bug #12833: Fixes closing print window in Chrome.
        (function() { win.close(); }).defer();
    },

    resizePopup: function(win)
    {
        var b = win.document.body,
            h = 0,
            w = 0;

        w = b.scrollWidth - b.clientWidth;
        if (w) {
            w = Math.min(w, screen.availWidth - win.outerWidth - 100);
        }
        h = b.scrollHeight - b.clientHeight;
        if (h) {
            h = Math.min(h, screen.availHeight - win.outerHeight - 100);
        }

        if (w || h) {
            win.resizeBy(w, h);
        }
    },

    fnv_1a: function(str)
    {
        var i, l,
            hash = 0x811c9dc5;

        for (i = 0, l = str.length; i < l; ++i) {
            hash ^= str.charCodeAt(i);
            hash += (hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24);
        }

        return hash >>> 0;
    },

    onDomLoad: function()
    {
        var mb = $('messageBody');

        if (mb) {
            mb.observe('scroll', function() {
                $('messageBody').select('IFRAME.htmlMsgData').each(this.iframeImgLazyLoad.bind(this));
            }.bind(this));
        }
    }

};

document.observe('dom:loaded', IMP_JS.onDomLoad.bind(IMP_JS));
