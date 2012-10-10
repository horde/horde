/**
 * Horde tooltips javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

var Horde_ToolTips =
{
    // Vars used and defaulting to null: attached, timeout

    attachBehavior: function()
    {
        if (!this.attached) {
            document.on('mouseover', 'a[nicetitle]', this.onMouseover.bindAsEventListener(this));
            document.on('mouseout', 'a[nicetitle]', this.out.bind(this));
            document.on('focus', 'a[nicetitle]', this.onFocus.bindAsEventListener(this));
            document.on('blur', 'a[nicetitle]', this.out.bind(this));
            this.attached = true;
        }
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
        this.timeout = this.show.bind(this, e, p).delay(0.3);
    },

    out: function()
    {
        var t = $('toolTip');

        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        if (t) {
            t.hide();
        }
    },

    show: function(e, pos)
    {
        var left, w,
            d = $('toolTip'),
            s_offset = document.viewport.getScrollOffsets(),
            v_dimens = document.viewport.getDimensions();

        if (d) {
            this.out();
        }

        if (!d) {
            d = new Element('DIV', {
                id: 'toolTip', className: 'nicetitle'
            }).hide();
            $(document.body).insert(d);
        }

        d.update('<pre>' + e.element().readAttribute('nicetitle').evalJSON(true).invoke('toString').invoke('escapeHTML').join("<br\>") + '</pre>');

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
    }

};

if (typeof Horde_ToolTips_Autoload == 'undefined' || !Horde_ToolTips_Autoload) {
    document.observe('dom:loaded', Horde_ToolTips.attachBehavior.bind(Horde_ToolTips));
}
