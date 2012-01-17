/**
 * Provides the javascript for the search.php script (basic view).
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Jan Schneider <jan@horde.org>
 */

TurbaSearch = {

    criteria: {},
    shareSources: {},

    updateCriteria: function()
    {
        var source_elt = $('turbaSearchSource');

        if (!source_elt || !source_elt.options) {
            return;
        }

        $('turbaSearchCriteria').update();
        $H(this.criteria[$F(source_elt)]).each(function(criterion) {
            $('turbaSearchCriteria').insert(new Element('option', { value: criterion.key }).insert(criterion.value.escapeHTML()));
        });

        if ($('vbook-form')) {
            if (this.shareSources[$F(source_elt)] == true) {
                $('vbook-form').show();
            } else {
                $('vbook-form').hide();
            }
        }
    }
}

document.observe('dom:loaded', TurbaSearch.updateCriteria.bind(TurbaSearch));
