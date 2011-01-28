/**
 * Provides the javascript for the search.php script (basic view).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

TurbaSearch = {

    criteria: {},
    shareSources: {},

    updateCriteria: function()
    {
        if (!$('turbaSearchSource').options) {
            return;
        }

        var source = $F('turbaSearchSource');

        $('turbaSearchCriteria').update();
        $H(this.criteria[source]).each(function(criterion) {
            $('turbaSearchCriteria').insert(new Element('option', { value: criterion.key }).insert(criterion.value.escapeHTML()));
        });

        if ($('vbook-form')) {
            if (this.shareSources[source] == true) {
                $('vbook-form').show();
            } else {
                $('vbook-form').hide();
            }
        }
    }
}

document.observe('dom:loaded', TurbaSearch.updateCriteria.bind(TurbaSearch));
