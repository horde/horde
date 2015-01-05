/**
 * Provides the javascript for the minisearch portal block.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.php.
 */

var TurbaMinisearch = {

    // Vars set by block code: abooks, URI_AJAX

    miniSearch: function(e)
    {
        $('turba-minisearch-searching', 'turba-minisearch-close', 'turba-minisearch-results').invoke('show');
        HordeCore.doAction('minisearch', {
            abooks: Object.toJSON(this.abooks),
            search: $F('turba-minisearch-search')
        }, {
            callback: function(r) {
                $('turba-minisearch-results').update(r.html);
                $('turba-minisearch-searching').hide();
            },
            uri: this.URI_AJAX
        });
        e.stop();
    },

    hideMiniSearch: function(e)
    {
        $('turba-minisearch-searching', 'turba-minisearch-close').invoke('hide');
        $('turba-minisearch-results').update().hide();
        e.stop();
    },

    onDomLoad: function()
    {
        $('turba-minisearch').observe('submit', this.miniSearch.bindAsEventListener(this));
        $('turba-minisearch-close').observe('click', this.hideMiniSearch.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', TurbaMinisearch.onDomLoad.bind(TurbaMinisearch));
