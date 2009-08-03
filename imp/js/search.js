/**
 * Provides the javascript for the search.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpSearch = {
    // The following variables are defined in search.php:
    //   inverse_sub, not_search, search_date

    _toggleAll: function(checked)
    {
        $('search').getInputs(null, 'search_folders[]').each(function(e) {
            e.checked = checked;
        });
    },

    _dateCheck: function(field)
    {
        var m = $('search_' + field + '_month'),
            d = $('search_' + field + '_day'),
            y = $('search_' + field + '_year');

        if (m.selectedIndex == 0) {
            m.selectedIndex = this.search_date.m;
        }

        if (d.selectedIndex == 0) {
            d.selectedIndex = this.search_date.d;
        }

        if (y.value == "") {
            y.value = this.search_date.y;
        }
    },

    _formCheck: function()
    {
        if (this.not_search &&
            (!$('preselected_folders') || !$F('preselected_folders'))) {
            if (!Form.getInputs('search', null, 'search_folders[]').detect(function(e) { return e.checked; })) {
                alert(IMP.text.search_select);
                return;
            }
        }

        $('actionID').setValue('do_search');
    },

    _reset: function()
    {
        $('actionID').setValue('reset_search');
        $('search').submit();
    },

    _saveCache: function()
    {
        $('edit_query').setValue($F('save_cache'));
        $('search').submit();
    },

    _deleteField: function(i)
    {
        $('delete_field_id').setValue(i);
        $('actionID').setValue('delete_field');
        $('search').submit();
    },

    _showSubscribed: function(i)
    {
        $('show_subscribed_only').setValue(i);
        $('search').submit();
    },

    changeHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        switch (id) {
        case 'save_cache':
            this._saveCache();
            break;

        default:
            if (id.startsWith('field_')) {
                $('search').submit();
            } else if (id.startsWith('search_date_')) {
                this._dateCheck('on');
            }
            break;
        }
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            if (elt.hasClassName('searchSubmit')) {
                this._formCheck();
            } else if (elt.hasClassName('searchReset')) {
                this._reset();
            } else if (elt.hasClassName('searchDelete')) {
                this._deleteField(elt.readAttribute('fid'));
            } else {
                switch (elt.readAttribute('id')) {
                case 'link_sel_all':
                    this._toggleAll(true);
                    break;

                case 'link_sel_none':
                    this._toggleAll(false);
                    break;

                case 'link_sub':
                    this._showSubscribed(this.inverse_sub);
                    break;

                case 'search_match_and':
                case 'search_match_or':
                    if ($('field_1')) {
                        $('search').submit();
                    }
                    break;
                }
            }

            elt = elt.up();
        }
    }

};

document.observe('change', ImpSearch.changeHandler.bind(ImpSearch));
document.observe('click', ImpSearch.clickHandler.bind(ImpSearch));
