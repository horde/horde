/**
 * Provides the javascript for the filters view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
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
                    $('filtersSave').clonePosition('filterslist').appear({
                       duration: 0.2
                    });

                    HordeCore.doAction(
                        'reSortFilters',
                        {
                            sort: Object.toJSON(Sortable.sequence('filterslist'))
                        },
                        {
                            callback: function() {
                                /* Need to re-label the IDs to reflect new
                                 * sort order. */
                                var i = 0,
                                    rows = $('filterslist').select('DIV.filtersRow');
                                rows.invoke('writeAttribute', 'id', null);
                                rows.each(function(r) {
                                    r.writeAttribute('id', 'filtersrow_' + (i++));
                                });

                                $('filtersSave').fade({ duration: 0.2 });
                            }
                        }
                    );
                },
                tag: 'div'
            });
        }
    }

};

document.observe('dom:loaded', IngoFilters.onDomLoad.bind(IngoFilters));
