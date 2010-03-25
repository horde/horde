/**
 * Provides the javascript for managing addressbooks.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var HordeAddressbooksPrefs = {

    // Variables set by other code: fields

    updateSearchFields: function()
    {
        var sv = this._getSelectedValue(false),
            sf = $('search_fields');

        sf.update('');
        this.fields.each(function(f) {
            if (f[0] == sv) {
                f.slice(1).each(function(o) {
                    var tmp = new Option(o[1], o[0]);
                    if (o[2]) {
                        tmp.selected = true;
                    }
                    sf.insert(tmp);
                });
            }
        });

        this.changeSearchFields();
    },

    _getSelectedValue: function(index)
    {
        var ss = $('selected_sources');
        if (ss) {
            if (index) {
                return ss.selectedIndex;
            }
            if (ss.selectedIndex >= 0) {
                return ss.options[ss.selectedIndex].value;
            }
            return '';
        } else {
            return index ? 0 : this.fields[0][0];
        }
    },

    changeSearchFields: function()
    {
        var data = [],
            i = 0,
            sf = $('search_fields'),
            sv = this._getSelectedValue(true);

        $A(sf.options).each(function(o) {
            this.fields[sv][i][2] = o.selected;
            ++i;
        }.bind(this));

        this.fields.each(function(f) {
            var tmp = [ f[0] ];
            f.slice(1).each(function(o) {
                if (o[2]) {
                    tmp.push(o[0]);
                }
            });
            data.push(tmp.join("\t"));
        });
        $('search_fields_string').setValue(data.join("\n"));
    },

    onDomLoad: function()
    {
        this.updateSearchFields();

        if ($('search_fields')) {
            $('search_fields').observe('change', this.changeSearchFields.bind(this));
        }

        if ($('selected_sources')) {
            $('selected_sources').observe('change', this.updateSearchFields.bind(this));
        }
    }

};

document.observe('dom:loaded', HordeAddressbooksPrefs.onDomLoad.bind(HordeAddressbooksPrefs));
