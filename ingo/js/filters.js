/**
 * Provides the javascript for the filters view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    ASL (http://www.horde.org/licenses/apache)
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

        if (window.Sortable) {
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
    }

};

document.observe('dom:loaded', IngoFilters.onDomLoad.bind(IngoFilters));
