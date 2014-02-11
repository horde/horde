/**
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 */

var IngoFilters = {

    onDomLoad: function()
    {
        if ($('apply_filters')) {
            $('apply_filters').observe('click', function(e) {
                $('actionID').setValue('apply_filters');
                $('filters').submit();
                e.stop();
            });
        }

        Sortable.create('filterslist', {
            onChange: function() {
                Horde.stripeElement('filterslist');
            },
            onUpdate: function() {
                HordeCore.doAction(
                    'reSortFilters',
                    { sort: Object.toJSON(Sortable.sequence('filterslist')) }
                );
            },
            tag: 'div'
        });
    }

};

document.observe('dom:loaded', IngoFilters.onDomLoad.bind(IngoFilters));
