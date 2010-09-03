/**
 * Horde tooltips javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var Horde_ToolTips =
{
    // Vars used and defaulting to null: element, timeout

    attachBehavior: function()
    {
        $$('a').each(this.attach.bind(this));
    },

    attach: function(e)
    {
        var t = e.readAttribute('title');
        if (!t) {
            return;
        }
        e.store('nicetitle', t);
        try {
            e.removeAttribute('title');
        } catch (e) {}
        e.observe('mouseover', this.onMouseover.bindAsEventListener(this));
        e.observe('mouseout', this.out.bind(this));
        e.observe('focus', this.onFocus.bindAsEventListener(this));
        e.observe('blur', this.out.bind(this));
    },

    detach: function(e)
    {
        e.stopObserving('mouseover');
        e.stopObserving('mouseout');
        e.stopObserving('focus');
        e.stopObserving('blur');
    },

    onMouseover: function(e)
    {
        this.onOver(e, [ e.pointerX(), e.pointerY() ]);
    },

    onFocus: function(e)
    {
        this.onOver(e, e.element().cumulativeOffset());
    },

    onOver: function(e, p)
    {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        this.element = e.element();
        this.timeout = this.show.bind(this, p).delay(0.3);
    },

    out: function()
    {
        var iframe, t = $('toolTip');

        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        if (t) {
            t.hide();

            iframe = $('iframe_tt');
            if (iframe) {
                iframe.hide();
            }
        }
    },

    show: function(pos)
    {
        var iframe, left, link, nicetitle, w,
            d = $('toolTip'),
            s_offset = document.viewport.getScrollOffsets(),
            v_dimens = document.viewport.getDimensions();

        if (d) {
            this.out();
        }

        link = this.element;
        while (!link.retrieve('nicetitle') && link.match('BODY')) {
            link = link.up();
        }

        nicetitle = link.retrieve('nicetitle');
        if (!nicetitle) {
            return;
        }

        if (!d) {
            d = new Element('DIV', { id: 'toolTip', className: 'nicetitle' }).hide();
            document.body.appendChild(d);
        }

        d.update(nicetitle);

        // Make sure all of the tooltip is visible.
        left = pos[0] + 10;
        w = d.getWidth();
        if ((left + w) > (v_dimens.width + s_offset.left)) {
            left = v_dimens.width - w - 40 + s_offset.left;
        }
        if (document.body.scrollWidth && ((left + w) > (document.body.scrollWidth + s_offset.left))) {
            left = document.body.scrollWidth - w - 25 + s_offset.left;
        }

        d.setStyle({
            left: Math.max(left, 5) + 'px',
            top: (pos[1] + 10) + 'px'
        }).show();

        // IE 6 only.
        if (Prototype.Browser.IE && !window.XMLHttpRequest) {
            iframe = $('iframe_tt');
            if (!iframe) {
                iframe = new Element('IFRAME', { name: 'iframe_tt', id: 'iframe_tt', src: 'javascript:false;', scrolling: 'no', frameborder: 0 }).hide();
                document.body.appendChild(iframe);
            }
            iframe.clonePosition(d).setStyle({
                position: 'absolute',
                display: 'block',
                zIndex: 99
            });
            d.setStyle({ zIndex: 100 });
        }
    }

};

if (typeof Horde_ToolTips_Autoload == 'undefined' || !Horde_ToolTips_Autoload) {
    Event.observe(window, 'load', Horde_ToolTips.attachBehavior.bind(Horde_ToolTips));
    Event.observe(window, 'unload', Horde_ToolTips.out.bind(Horde_ToolTips));
}
