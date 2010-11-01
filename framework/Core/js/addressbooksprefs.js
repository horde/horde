/**
 * Provides the javascript for managing addressbooks.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 */

var HordeAddressbooksPrefs = {

    // Variables set by other code: fields, nonetext

    updateSearchFields: function()
    {
        var tmp,
            sv = $F('selected_sources'),
            sf = $('search_fields_select');

        sf.childElements().invoke('remove');

        if (sv.size() == 1) {
            tmp = this.fields.get(sv.first());
            tmp.entries.each(function(o) {
                var opt = new Option(o.label, o.name);
                if (tmp.selected.include(o.name)) {
                    opt.selected = true;
                }
                sf.insert(opt);
            });
        } else {
            tmp = new Option(this.nonetext, '');
            tmp.disabled = true;
            sf.insert(tmp);
        }
    },

    changeSearchFields: function()
    {
        var tmp,
            out = $H(),
            sv = $F('selected_sources');

        if (sv.size() == 1) {
            tmp = this.fields.get(sv.first());
            tmp.selected = $F('search_fields_select');
            this.fields.set(sv.first(), tmp);

            this.fields.each(function(f) {
                out.set(f.key, f.value.selected);
            });

            $('search_fields').setValue(Object.toJSON(out));
        }
    },

    onDomLoad: function()
    {
        this.fields = $H(this.fields);

        this.updateSearchFields();

        $('search_fields_select').observe('change', this.changeSearchFields.bind(this));
        $('selected_sources').observe('change', this.updateSearchFields.bind(this));
        $('selected_sources').observe('HordeSourceSelectPrefs:remove', this.updateSearchFields.bind(this));
    }

};

document.observe('dom:loaded', HordeAddressbooksPrefs.onDomLoad.bind(HordeAddressbooksPrefs));
