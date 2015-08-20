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
                                    rows = $('filterslist').childElements();
                                rows.invoke('writeAttribute', 'id', null);
                                rows.each(function(r) {
                                    var a = r.select('a');
                                    r.writeAttribute('id', 'filtersrow_' + (i++));
                                    a.each(function(l) {
                                        var href_new = IngoFilters.replaceQueryParam('rulenumber', i - 1, l.href);
                                        href_new = IngoFilters.replaceQueryParam('edit', i - 1, href_new);
                                        l.setAttribute('href', href_new);
                                   });
                                });
                                $('filtersSave').fade({ duration: 0.2 });
                            }
                        }
                    );
                },
                tag: 'div'
            });
        }

    },

    // Adapted from http://stackoverflow.com/questions/1090948/change-url-parameters
    replaceQueryParam: function(param, newval, search) {
        var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
        var query = search.replace(regex, "$1").replace(/&$/, '');
        return (query.length > 2 ? query + "&" : "?") + (param + "=" + newval);
    }

};

document.observe('dom:loaded', IngoFilters.onDomLoad.bind(IngoFilters));
