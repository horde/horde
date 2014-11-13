/**
 * Basic IMP javascript functions.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var IMP_JS = {

    iframeresize_run: {},
    iframe_y: {},
    lazyload_run: {},
    resize_delay: 0.01,

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

        if (elt.readAttribute('noUnblockImageAdd')) {
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

        callback = this.iframeResize.bind(this, iframe);
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

        this.iframeResize(iframe);
    },

    iframeInject: function(id, data)
    {
        if (!(id = $(id))) {
            return;
        }

        var d = this.iframeDoc(id), ev;

        id.onload = function() {
            this.iframeResize(id);
            this.iframeOverflowY(id, true);
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

        this.iframeOverflowY(id, false);
        id.show().previous().remove();
        this.iframeResize(id);
    },

    // iframe = (Element)
    iframeResize: function(iframe)
    {
        var id = iframe.identify();

        // IE (at a minimum) needs a slight delay to size properly
        if (!this.iframeresize_run[id]) {
            this.iframeresize_run[id] = true;
            this.iframeResizeRun.bind(this, iframe).delay(this.resize_delay);
        }
    },

    iframeResizeRun: function(id)
    {
        var body, h1, h2, html, iHeight;

        body = this.iframeDoc(id).body;
        html = body.parentNode;
        iHeight = function() {
            return Math.max(
                body.offsetHeight,
                // IE 8 only
                (Prototype.Browser.IE && !document.addEventListener) ? body.scrollHeight : 0,
                html.offsetHeight,
                html.scrollHeight
            );
        };

        Element.setStyle(body, { height: null });

        h1 = iHeight();
        id.setStyle({ height: h1 + 'px' });

        h2 = iHeight();
        if (h2 > h1) {
            id.setStyle({ height: h2 + 'px' });
        }

        this.iframeImgLazyLoad(id);

        this.iframeresize_run[id.identify()] = false;
    },

    iframeImgLazyLoad: function(iframe)
    {
        var id = iframe.identify();

        if (!this.lazyload_run[id]) {
            this.lazyload_run[id] = true;
            this.iframeImgLazyLoadRun.bind(this, iframe)
                .delay(this.resize_delay);
        }
    },

    iframeImgLazyLoadRun: function(iframe)
    {
        var error, imgs, mb_height, range_top, range_bottom, resize,
            mb = this.messageBody();

        mb_height = mb.getHeight();

        /* Load messages within 1 scrolled page of range boundaries. */
        range_top = mb.scrollTop - mb_height;
        range_bottom = mb.scrollTop + (2 * mb_height);

        imgs = Prototype.Selector.select('IMG[data-src]', this.iframeDoc(iframe)).findAll(Element.visible);

        if (imgs.size()) {
            iframe.setStyle({ overflowY: 'hidden' });

            error = this.iframeOverflowY.bind(this, iframe);
            resize = function() {
                this.iframeResize(iframe);
                this.iframeOverflowY(iframe, true);
            }.bind(this);

            imgs.each(function(img) {
                var co = Element.cumulativeOffset(img);
                if (co.top > range_top && co.top < range_bottom) {
                    this.iframeOverflowY(iframe, false);
                    img.onerror = error;
                    img.onload = resize;
                    Element.writeAttribute(img, 'src', Element.readAttribute(img, 'data-src'));
                    Element.writeAttribute(img, 'data-src', null);
                }
            }, this);
        }

        this.lazyload_run[iframe.identify()] = false;
    },

    iframeDoc: function(i)
    {
        return i.contentDocument || i.contentWindow.document;
    },

    iframeOverflowY: function(id, show)
    {
        var key = id.identify();

        if (show) {
            if (this.iframe_y[key] && !(--this.iframe_y[key])) {
                id.setStyle({ overflowY: '' });
                delete this.iframe_y[key];
            }
        } else {
            if (this.iframe_y[key]) {
                ++this.iframe_y[key];
            } else {
                id.setStyle({ overflowY: 'hidden' });
                this.iframe_y[key] = 1;
            }
        }
    },

    messageBody: function()
    {
        return $('previewPane') || $('messageBody');
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
        var mb = this.messageBody();

        if (mb) {
            mb.observe('scroll', function() {
                $('messageBody').select('IFRAME.htmlMsgData').each(this.iframeImgLazyLoad.bind(this));
            }.bind(this));
        }
    }

};

document.observe('dom:loaded', IMP_JS.onDomLoad.bind(IMP_JS));
