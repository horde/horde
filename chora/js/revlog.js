/**
 * Revision log javascript.
 */

var Chora_RevLog = {

    selected: null,

    highlight: function()
    {
        revlog_body = $('revlog_body');
        if (revlog_body) {
            revlog_body.select('TR').each(function(tr) {
                if (Prototype.Browser.IE) {
                    tr.observe('mouseover', this.rowover.bindAsEventListener(this, 'over'));
                    tr.observe('mouseover', this.rowover.bindAsEventListener(this, 'out'));
                }
                tr.observe('click', this.toggle.bindAsEventListener(this));
            }, this);
        }
    },

    rowover: function(e, type)
    {
        e.element().invoke(type == 'over' ? 'addClassName' : 'removeClassName', 'hover');
    },

    toggle: function(e)
    {
        // Ignore clicks on links.
        var elt = e.element();
        if (elt.tagName.toUpperCase() != 'TR') {
            if (elt.tagName.toUpperCase() == 'A' &&
                elt.readAttribute('href')) {
                return;
            }
            elt = elt.up('TR');
        }

        if (this.selected != null) {
            this.selected.removeClassName('selected');
            if (this.selected == elt) {
                this.selected = null;
                $('revlog_body').removeClassName('selection');
                return;
            }
        }

        this.selected = elt;
        elt.addClassName('selected');
        $('revlog_body').addClassName('selection');
    },

    sdiff: function(link)
    {
        link = $(link);
        link.writeAttribute('href', link.readAttribute('href').replace(/r1=([\d\.]+)/, 'r1=' + this.selected.identify().substring(3)));
    }
};

document.observe('dom:loaded', Chora_RevLog.highlight.bind(Chora_RevLog));
