/**
 * Provides the javascript for managing columns.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var TurbaColumnPrefs = {

    // Vars defaulting to null: cur_source

    updateColumnsPref: function()
    {
        $('columns').value = $$('.turba-prefs-cols-panel').collect(this.updateColumnsPref_1.bind(this)).join("\n");
    },

    updateColumnsPref_1: function(panel)
    {
        var c, p = panel.id.replace('turba-prefs-cols-panel-', '');
        this.cur_source = p;
        c = panel.select('input[type=checkbox]:checked').collect(this.updateColumnsPref_2.bind(this)).join("\t");
        if (c) {
            p += "\t" + c;
        }
        return p;
    },

    updateColumnsPref_2: function(checkbox)
    {
        if (checkbox.checked) {
            return checkbox.id.replace('turba-prefs-cols-' + this.cur_source + '-', '');
        }
    },

    clickHandler: function(e)
    {
        var elt = e.element();
        $('turba-prefs-cols-list').select('.active').invoke('removeClassName', 'active');
        elt.up().addClassName('active');
        $$('.turba-prefs-cols-panel').invoke('hide');
        $('turba-prefs-cols-panel-' + elt.readAttribute('sourcename')).show();
    },

    onDomLoad: function()
    {
        $('turba-prefs-cols-columns').select('OL').each(function(ol) {
            Sortable.create(ol, { onUpdate: this.updateColumnsPref.bind(this) });
        }, this);

        $('turba-prefs-cols-columns').observe('click', function(e) {
            if (e.element().match('input[type=checkbox]')) {
                this.updateColumnsPref();
            }
        }.bindAsEventListener(this));

        $('turba-prefs-cols-list').select('A').invoke('observe', 'click', this.clickHandler.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', TurbaColumnPrefs.onDomLoad.bind(TurbaColumnPrefs));
