/**
 * Provides the javascript for the minisearch portal block.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

var TurbaMinisearch = {

    // Vars set by block code: abooks, URI_AJAX

    miniSearch: function(e)
    {
        $('turba_minisearch_searching', 'turba_minisearch_close', 'turba_minisearch_results').invoke('show');
        HordeCore.doAction('minisearch', {
            abooks: Object.toJSON(this.abooks),
            search: $F('turba_minisearch_search')
        }, {
            callback: function(r) {
                $('turba_minisearch_results').update(r.html);
                $('turba_minisearch_searching').hide();
            },
            uri: this.URI_AJAX
        });
        e.stop();
    },

    hideMiniSearch: function(e)
    {
        $('turba_minisearch_searching', 'turba_minisearch_close').invoke('hide');
        $('turba_minisearch_results').update().hide();
        e.stop();
    },

    onDomLoad: function()
    {
        $('turba_minisearch').observe('submit', this.miniSearch.bindAsEventListener(this));
        $('turba_minisearch_close').observe('click', this.hideMiniSearch.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', TurbaMinisearch.onDomLoad.bind(TurbaMinisearch));
