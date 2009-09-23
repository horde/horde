/**
 * Provides the javascript for managing addressbooks.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpAddressbooksPrefs = {
    // Variables set by other code: fields

    deselectHeaders: function()
    {
        $('unselected_search_sources').selectedIndex = $('selected_search_sources').selectedIndex = -1;
    },

    resetHidden: function()
    {
        $('search_sources').setValue($F('selected_search_sources').join("\t"));
    },

    addSource: function()
    {
        this._sourceAction($('unselected_search_sources'), $('selected_search_sources'));
    },

    removeSource: function()
    {
        this._sourceAction($('selected_search_sources'), $('unselected_search_sources'));
    },

    _sourceAction: function(from, to)
    {
        var i = 1;

        $A(from.options).slice(1).each(function(s) {
            if (s.selected) {
                to.appendChild(s.cloneNode(true));
                s.remove();
            }
            ++i;
        });

        this.resetHidden();
    },

    moveSourceUp: function()
    {
        var sss = $('selected_search_sources'),
            sel = sss.selectedIndex;

        if (sel == -1 || sss.length <= 2) {
            return;
        }

        // Deselect everything but the first selected item
        sss.selectedIndex = sel;

        sss.options[sel].previous().insert({ before: sss.options[sel].cloneNode(false) });
        sss.options[sel].remove();

        this.resetHidden();
    },

    moveSourceDown: function()
    {
        var i,
            sss = $('selected_search_sources'),
            sel = sss.selectedIndex,
            l = sss.length,
            tmp = [];

        if (sel == -1 || l <= 2) {
            return;
        }

        // deselect everything but the first selected item
        sss.selectedIndex = sel;

        sss.options[sel].next().insert({ after: sss.options[sel].cloneNode(false) });
        sss.options[sel].remove();

        this.resetHidden();
    },

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
        var sss = $('selected_search_sources');
        if (sss) {
            if (index) {
                return sss.selectedIndex;
            }
            if (sss.selectedIndex >= 0) {
                return sss.options[sss.selectedIndex].value;
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

        if ($('unselected_search_sources')) {
            //$('unselected_search_sources').observe('change', this.deselectHeaders.bind(this));
            $('selected_search_sources').observe('change', function() {
                //this.deselectHeaders();
                this.updateSearchFields();
            }.bind(this));
            $('addsource').observe('click', this.addSource.bind(this));
            $('removesource').observe('click', this.removeSource.bind(this));
            $('moveup').observe('click', this.moveSourceUp.bind(this));
            $('movedown').observe('click', this.moveSourceDown.bind(this));
        }
    }

};

document.observe('dom:loaded', ImpAddressbooksPrefs.onDomLoad.bind(ImpAddressbooksPrefs));
