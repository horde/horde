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
            onUpdate: function() {
                $('filtersSort').show();
                Horde.stripeElement('filterslist');
            },
            tag: 'div'
        });

        $('filtersSort').down('INPUT').observe('click', function(e) {
            $('actionID').setValue('update_sort');
            $('sort_order').setValue(Object.toJSON(Sortable.sequence('filterslist')));
            $('filters').submit();
            e.stop();
        });
    }

};

document.observe('dom:loaded', IngoFilters.onDomLoad.bind(IngoFilters));
